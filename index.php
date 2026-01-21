<?php
/**
 * Project Automator Engine - Main Dashboard
 * Enterprise Grade - High Stability Architecture
 */

session_start();

// --- Configuration & Security ---
$session_timeout = 1800; // 30 Minutes
define('PROJECT_NAME', 'AI_ENGINE_PRO');

// Check Session Expiry
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?reason=expired");
    exit;
}

// Redirect to login if not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$_SESSION['last_activity'] = time(); // Refresh activity
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PROJECT_NAME; ?> | Dashboard</title>
    
    <!-- Tailwind CSS for Modern UI -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CodeMirror 6 Styles & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .cm-editor { height: 100%; font-size: 14px; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        .file-tree-item:hover { background-color: rgba(63, 63, 70, 0.5); }
    </style>
</head>
<body class="h-full text-zinc-300 flex flex-col overflow-hidden">

    <!-- Top Navigation Bar (GitHub & Session) -->
    <header class="h-14 border-b border-zinc-800 bg-zinc-900 flex items-center justify-between px-6 shrink-0">
        <div class="flex items-center gap-4">
            <i class="fa-solid fa-microchip text-indigo-500 text-xl"></i>
            <h1 class="font-bold tracking-tight text-zinc-100"><?php echo PROJECT_NAME; ?></h1>
            <div class="h-4 w-[1px] bg-zinc-700 mx-2"></div>
            <span class="text-xs bg-zinc-800 px-2 py-1 rounded border border-zinc-700">Root: /var/www/html</span>
        </div>

        <div class="flex items-center gap-6">
            <!-- GitHub GUI Controls -->
            <div class="flex items-center gap-3 bg-zinc-950 p-1 rounded-lg border border-zinc-800">
                <select id="branchSelector" class="bg-transparent text-xs border-none focus:ring-0 cursor-pointer">
                    <option value="main">main</option>
                    <option value="develop">develop</option>
                </select>
                <button title="New Branch" class="hover:text-indigo-400 transition-colors">
                    <i class="fa-solid fa-plus-circle"></i>
                </button>
                <div class="h-4 w-[1px] bg-zinc-800"></div>
                <button id="btnPush" class="flex items-center gap-2 px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded transition-all">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Sync to GitHub
                </button>
            </div>

            <div class="flex items-center gap-4 border-l border-zinc-800 pl-6 text-sm">
                <span id="sessionTimer" class="text-zinc-500 font-mono">30:00</span>
                <a href="logout.php" class="text-zinc-400 hover:text-red-400"><i class="fa-solid fa-power-off"></i></a>
            </div>
        </div>
    </header>

    <main class="flex flex-1 overflow-hidden">
        
        <!-- Sidebar: File Tree -->
        <aside class="w-64 border-right border-zinc-800 bg-zinc-900 flex flex-col shrink-0">
            <div class="p-4 flex justify-between items-center border-b border-zinc-800">
                <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">Project Explorer</span>
                <button onclick="refreshFileTree()" class="text-zinc-500 hover:text-zinc-200"><i class="fa-solid fa-rotate"></i></button>
            </div>
            <div id="fileTree" class="flex-1 overflow-y-auto p-2 text-sm">
                <!-- Loaded dynamically via API -->
                <div class="animate-pulse flex flex-col gap-2 p-2">
                    <div class="h-4 bg-zinc-800 rounded w-3/4"></div>
                    <div class="h-4 bg-zinc-800 rounded w-1/2"></div>
                    <div class="h-4 bg-zinc-800 rounded w-2/3"></div>
                </div>
            </div>
        </aside>

        <!-- Main Content: Code Editor -->
        <section class="flex-1 flex flex-col bg-zinc-950 relative">
            <div id="editorTabs" class="h-10 bg-zinc-900 border-b border-zinc-800 flex items-center px-4 gap-2">
                <span class="text-xs text-zinc-400 italic" id="activeFileName">No file selected</span>
            </div>
            <div id="editorContainer" class="flex-1 overflow-hidden">
                <!-- CodeMirror 6 will mount here -->
            </div>
            <!-- Status Bar -->
            <div class="h-8 bg-zinc-900 border-t border-zinc-800 flex items-center justify-between px-4 text-[10px] text-zinc-500">
                <div id="editorStatus">Line 1, Column 1</div>
                <div id="saveIndicator" class="hidden text-green-500 font-bold uppercase">Changes Saved</div>
            </div>
        </section>

        <!-- Right Panel: AI Engine & Output Logs -->
        <aside class="w-80 border-l border-zinc-800 bg-zinc-900 flex flex-col shrink-0">
            <div class="p-4 border-b border-zinc-800">
                <h2 class="text-sm font-bold text-indigo-400"><i class="fa-solid fa-bolt mr-2"></i>AI Engine</h2>
            </div>
            
            <!-- Upload Zone -->
            <div class="p-4">
                <label for="aiUpload" class="group relative flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-zinc-700 rounded-lg cursor-pointer hover:border-indigo-500 hover:bg-zinc-800/50 transition-all">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fa-solid fa-file-import text-zinc-500 group-hover:text-indigo-400 mb-2"></i>
                        <p class="text-xs text-zinc-500"><span class="font-semibold text-zinc-300">Drop AI Instructions</span></p>
                    </div>
                    <input id="aiUpload" type="file" class="hidden" accept=".txt,.md" onchange="handleAIUpload(this)" />
                </label>
            </div>

            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="px-4 py-2 border-y border-zinc-800 text-[10px] font-bold text-zinc-500 bg-zinc-950 uppercase tracking-widest">
                    Execution Logs
                </div>
                <div id="engineLogs" class="flex-1 overflow-y-auto p-4 font-mono text-[11px] space-y-2 text-zinc-400">
                    <div class="text-zinc-600 italic">> Engine standby... ready for upload.</div>
                </div>
            </div>
        </aside>
    </main>

    <!-- Modal for Commit Message -->
    <div id="commitModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
        <div class="bg-zinc-900 border border-zinc-800 p-6 rounded-xl w-96 shadow-2xl">
            <h3 class="text-lg font-bold mb-4 text-white">Push to GitHub</h3>
            <textarea id="commitMsg" class="w-full h-24 bg-zinc-950 border border-zinc-800 rounded p-3 text-sm text-zinc-300 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Enter commit message..."></textarea>
            <div class="flex justify-end gap-3 mt-4">
                <button onclick="toggleCommitModal()" class="px-4 py-2 text-sm text-zinc-400 hover:text-white">Cancel</button>
                <button onclick="executePush()" class="px-4 py-2 text-sm bg-indigo-600 rounded hover:bg-indigo-500 text-white font-bold">Confirm Push</button>
            </div>
        </div>
    </div>

    <!-- Modules Load (Import Map for CM6) -->
    <script type="importmap">
    {
        "imports": {
            "codemirror": "https://esm.sh/codemirror@6.0.1",
            "@codemirror/view": "https://esm.sh/@codemirror/view@6.0.0",
            "@codemirror/state": "https://esm.sh/@codemirror/state@6.0.0",
            "@codemirror/language": "https://esm.sh/@codemirror/language@6.0.0",
            "@codemirror/commands": "https://esm.sh/@codemirror/commands@6.0.0",
            "@codemirror/theme-one-dark": "https://esm.sh/@codemirror/theme-one-dark@6.0.0"
        }
    }
    </script>
    <script type="module" src="assets/js/editor.js"></script>
    <script src="assets/js/ui_interactions.js"></script>
</body>
</html>
