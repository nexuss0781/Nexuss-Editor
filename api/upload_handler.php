<?php
require_once '../auth.php';
require_once '../engine/Parser.php';
require_once '../engine/Filesystem.php';

use Engine\Parser;
use Engine\Filesystem;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['ai_file'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

try {
    $content = file_get_contents($_FILES['ai_file']['tmp_name']);
    
    $parser = new Parser();
    $tasks = $parser->parse($content);
    
    if (empty($tasks)) {
        echo json_encode(['status' => 'warning', 'message' => 'No valid instructions found in file.']);
        exit;
    }

    $fs = new Filesystem(PROJECT_ROOT);
    $logs = $fs->executeTasks($tasks);

    echo json_encode([
        'status' => 'success',
        'tasks_count' => count($tasks),
        'logs' => $logs
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
