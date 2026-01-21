/**
 * Project Automator Engine - UI Logic Controller
 * Handles GitHub GUI, Modals, and Session Management
 */

// --- Global State ---
let sessionSeconds = 1800; // 30 Minutes

document.addEventListener("DOMContentLoaded", () => {
    startSessionTimer();
    loadGitHubBranches();
    setupTooltips();
});

/**
 * 1. Session Management
 * Visually updates the 30-minute countdown and redirects on expiry
 */
function startSessionTimer() {
    const timerElement = document.getElementById("sessionTimer");
    
    const countdown = setInterval(() => {
        sessionSeconds--;
        
        let mins = Math.floor(sessionSeconds / 60);
        let secs = sessionSeconds % 60;
        timerElement.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

        if (sessionSeconds <= 300) { // Warning color at 5 minutes
            timerElement.classList.replace("text-zinc-500", "text-red-500");
        }

        if (sessionSeconds <= 0) {
            clearInterval(countdown);
            window.location.href = "login.php?reason=expired";
        }
    }, 1000);
}

/**
 * 2. GitHub: Branch Loading
 */
async function loadGitHubBranches() {
    const selector = document.getElementById("branchSelector");
    try {
        const response = await fetch("api/git_actions.php?action=list_branches");
        const data = await response.json();

        if (data.status === "success") {
            selector.innerHTML = data.branches.map(b => 
                `<option value="${b.name}" ${b.name === 'main' ? 'selected' : ''}>${b.name}</option>`
            ).join('');
        }
    } catch (err) {
        logToEngine("Failed to fetch branches from GitHub", "ERROR");
    }
}

/**
 * 3. GitHub: Branch Creation GUI
 */
window.promptCreateBranch = async () => {
    const branchName = prompt("Enter new branch name:");
    if (!branchName) return;

    const formData = new FormData();
    formData.append("action", "create_branch");
    formData.append("name", branchName);

    logToEngine(`Creating branch: ${branchName}...`, "SYSTEM");

    const response = await fetch("api/git_actions.php", { method: "POST", body: formData });
    const result = await response.json();

    if (result.status === "success") {
        logToEngine(result.message, "SUCCESS");
        loadGitHubBranches();
    } else {
        logToEngine(result.message, "ERROR");
    }
};

/**
 * 4. GitHub: Push/Commit Modal Logic
 */
window.toggleCommitModal = () => {
    const modal = document.getElementById("commitModal");
    modal.classList.toggle("hidden");
};

window.executePush = async () => {
    const btn = document.getElementById("btnPush");
    const msg = document.getElementById("commitMsg").value;
    const branch = document.getElementById("branchSelector").value;

    toggleCommitModal();
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Syncing...`;
    
    logToEngine(`Syncing all files to branch [${branch}]...`, "SYSTEM");

    const formData = new FormData();
    formData.append("action", "push");
    formData.append("message", msg);
    formData.append("branch", branch);

    try {
        const response = await fetch("api/git_actions.php", { method: "POST", body: formData });
        const result = await response.json();

        if (result.status === "success") {
            logToEngine(result.message, "SUCCESS");
        } else {
            logToEngine(result.message, "ERROR");
        }
    } catch (err) {
        logToEngine("Critical Git Sync Failure", "ERROR");
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-cloud-arrow-up"></i> Sync to GitHub`;
        document.getElementById("commitMsg").value = ""; // Clear input
    }
};

/**
 * 5. Repository Creation Utility
 */
window.createNewRepo = async () => {
    const repoName = prompt("Enter new Repository Name:");
    if (!repoName) return;

    const visibility = confirm("Make Repository Private? (Cancel for Public)") ? "private" : "public";
    
    const formData = new FormData();
    formData.append("action", "create_repo");
    formData.append("name", repoName);
    formData.append("visibility", visibility);

    logToEngine(`Provisioning new ${visibility} repo: ${repoName}...`, "SYSTEM");

    const response = await fetch("api/git_actions.php", { method: "POST", body: formData });
    const result = await response.json();

    if (result.status === "success") {
        logToEngine(result.message, "SUCCESS");
    } else {
        logToEngine(result.message, "ERROR");
    }
};

/**
 * 6. UI Helpers
 */
function setupTooltips() {
    // Basic helper for future UI enhancements
    console.log("UI Framework initialized.");
}

// Map the Push button to the modal
document.getElementById("btnPush").onclick = window.toggleCommitModal;

// Global log bridge (re-used from editor.js logic if needed)
function logToEngine(message, type = "INFO") {
    const console = document.getElementById("engineLogs");
    if (!console) return;
    const div = document.createElement("div");
    const colors = {
        "ERROR": "text-red-400 font-bold",
        "SUCCESS": "text-green-400 font-bold",
        "SYSTEM": "text-blue-400 italic"
    };
    div.className = `log-entry ${colors[type] || "text-zinc-400"}`;
    div.innerHTML = `<span class="opacity-30">[${new Date().toLocaleTimeString()}]</span> ${message}`;
    console.prepend(div);
}
