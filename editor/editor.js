/**
 * Project Automator Engine - Frontend Controller
 * Powered by CodeMirror 6
 */

import { EditorView, basicSetup } from "codemirror";
import { EditorState } from "@codemirror/state";
import { javascript } from "https://esm.sh/@codemirror/lang-javascript@6.0.0";
import { php } from "https://esm.sh/@codemirror/lang-php@6.0.0";
import { oneDark } from "@codemirror/theme-one-dark";

let editor = null;
let currentFilePath = null;
let saveTimeout = null;

// --- Initialization ---
document.addEventListener("DOMContentLoaded", () => {
    initEditor();
    refreshFileTree();
    setupEventListeners();
});

/**
 * Initializes CodeMirror 6 with a Dark Theme and Basic Setup
 */
function initEditor() {
    const container = document.getElementById("editorContainer");
    
    const startState = EditorState.create({
        doc: "// Select a file from the explorer to begin...",
        extensions: [
            basicSetup,
            oneDark,
            php(), // Defaulting to PHP language support
            EditorView.updateListener.of((update) => {
                if (update.docChanged) {
                    handleDocChange();
                }
                updateCursorPosition(update);
            })
        ]
    });

    editor = new EditorView({
        state: startState,
        parent: container
    });
}

/**
 * Loads a file from the server into the editor
 */
async function loadFile(path) {
    try {
        const response = await fetch(`api/file_manager.php?action=read&path=${encodeURIComponent(path)}`);
        const data = await response.json();

        if (data.error) throw new Error(data.error);

        const newState = EditorState.create({
            doc: data.content,
            extensions: [basicSetup, oneDark, php()]
        });

        editor.setState(newState);
        currentFilePath = path;
        document.getElementById("activeFileName").innerText = path;
        logToEngine(`Loaded file: ${path}`, "SYSTEM");
    } catch (err) {
        logToEngine(err.message, "ERROR");
    }
}

/**
 * Automatically triggers a save after 1 second of inactivity
 */
function handleDocChange() {
    if (!currentFilePath) return;

    clearTimeout(saveTimeout);
    document.getElementById("saveIndicator").classList.add("hidden");

    saveTimeout = setTimeout(async () => {
        const content = editor.state.doc.toString();
        const formData = new FormData();
        formData.append("action", "save");
        formData.append("path", currentFilePath);
        formData.append("content", content);

        const response = await fetch("api/file_manager.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();
        if (result.status === "success") {
            const indicator = document.getElementById("saveIndicator");
            indicator.classList.remove("hidden");
            setTimeout(() => indicator.classList.add("hidden"), 2000);
        }
    }, 1000);
}

/**
 * Handles the AI File Upload and updates the UI
 */
window.handleAIUpload = async (input) => {
    if (!input.files.length) return;

    logToEngine("Parsing AI Instructions...", "ENGINE");
    
    const formData = new FormData();
    formData.append("ai_file", input.files[0]);

    try {
        const response = await fetch("api/upload_handler.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.status === "success") {
            result.logs.forEach(log => {
                logToEngine(log.message, log.type);
            });
            refreshFileTree();
            logToEngine("AI execution completed successfully.", "SUCCESS");
        } else {
            logToEngine(result.message, "ERROR");
        }
    } catch (err) {
        logToEngine("Failed to execute AI script.", "ERROR");
    }
};

/**
 * Fetches and renders the sidebar file tree
 */
async function refreshFileTree() {
    const container = document.getElementById("fileTree");
    try {
        const response = await fetch("api/get_file_tree.php");
        const data = await response.json();
        
        container.innerHTML = renderTree(data);
    } catch (err) {
        container.innerHTML = `<div class="text-red-500 text-xs">Failed to load tree</div>`;
    }
}

function renderTree(items) {
    return items.map(item => {
        if (item.type === 'folder') {
            return `
                <div class="mb-1">
                    <div class="flex items-center gap-2 px-2 py-1 text-zinc-400 font-bold cursor-default">
                        <i class="fa-solid fa-folder-open text-amber-500 text-xs"></i> ${item.name}
                    </div>
                    <div class="pl-4 border-l border-zinc-800 ml-3">
                        ${renderTree(item.children)}
                    </div>
                </div>`;
        } else {
            return `
                <div class="file-tree-item flex items-center gap-2 px-2 py-1 cursor-pointer rounded transition-colors" 
                     onclick="window.loadFile('${item.path}')">
                    <i class="fa-solid fa-file-code text-indigo-400 text-xs"></i> 
                    <span>${item.name}</span>
                </div>`;
        }
    }).join('');
}

/**
 * UI Helper: Logs messages to the right-side console
 */
function logToEngine(message, type = "INFO") {
    const console = document.getElementById("engineLogs");
    const div = document.createElement("div");
    const colors = {
        "ERROR": "text-red-400",
        "SUCCESS": "text-green-400",
        "DIR": "text-amber-400",
        "REPLACE": "text-indigo-400",
        "FILE": "text-emerald-400"
    };
    
    div.className = colors[type] || "text-zinc-400";
    div.innerHTML = `<span class="opacity-50">[${new Date().toLocaleTimeString()}]</span> [${type}] ${message}`;
    console.prepend(div);
}

function updateCursorPosition(update) {
    if (update.selection) {
        const pos = update.state.selection.main.head;
        const line = update.state.doc.lineAt(pos);
        document.getElementById("editorStatus").innerText = `Line ${line.number}, Column ${pos - line.from + 1}`;
    }
}

// Global exposure for HTML onclicks
window.loadFile = loadFile;
window.refreshFileTree = refreshFileTree;
