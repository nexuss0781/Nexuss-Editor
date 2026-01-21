<?php
require_once '../auth.php';

header('Content-Type: application/json');

/**
 * Recursively scans the directory and builds a tree structure
 */
function scanProjectDir($dir) {
    $result = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.git') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $relativePath = ltrim(str_replace(PROJECT_ROOT, '', $fullPath), DIRECTORY_SEPARATOR);
        
        $node = [
            'name' => $item,
            'path' => $relativePath,
            'type' => is_dir($fullPath) ? 'folder' : 'file'
        ];

        if ($node['type'] === 'folder') {
            $node['children'] = scanProjectDir($fullPath);
        }

        $result[] = $node;
    }

    // Sort folders first, then files
    usort($result, function($a, $b) {
        if ($a['type'] === $b['type']) return strcasecmp($a['name'], $b['name']);
        return ($a['type'] === 'folder') ? -1 : 1;
    });

    return $result;
}

try {
    $tree = scanProjectDir(PROJECT_ROOT);
    echo json_encode($tree);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
