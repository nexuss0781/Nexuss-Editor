<?php
require_once '../auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$filePath = $_GET['path'] ?? $_POST['path'] ?? null;

if (!$filePath) {
    echo json_encode(['error' => 'No file path provided']);
    exit;
}

// Security: Prevent directory traversal
$fullPath = realpath(PROJECT_ROOT . DIRECTORY_SEPARATOR . $filePath);
if ($fullPath === false || strpos($fullPath, realpath(PROJECT_ROOT)) !== 0) {
    echo json_encode(['error' => 'Access denied: Path outside project root']);
    exit;
}

switch ($action) {
    case 'read':
        if (!file_exists($fullPath)) {
            echo json_encode(['error' => 'File not found']);
        } else {
            echo json_encode([
                'content' => file_get_contents($fullPath),
                'path' => $filePath
            ]);
        }
        break;

    case 'save':
        $content = $_POST['content'] ?? '';
        if (file_put_contents($fullPath, $content) !== false) {
            echo json_encode(['status' => 'success', 'message' => 'File saved successfully']);
        } else {
            echo json_encode(['error' => 'Failed to write to file']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
