<?php
require_once '../auth.php';
require_once '../engine/GitHubClient.php';

use Engine\GitHubClient;

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$client = new GitHubClient(GH_TOKEN, GH_OWNER, GH_REPO);

try {
    switch ($action) {
        case 'list_branches':
            $branches = $client->getBranches();
            echo json_encode(['status' => 'success', 'branches' => $branches]);
            break;

        case 'create_branch':
            $name = $_POST['name'];
            $result = $client->createBranch($name);
            echo json_encode(['status' => 'success', 'message' => "Branch '$name' created."]);
            break;

        case 'create_repo':
            $name = $_POST['name'];
            $isPrivate = ($_POST['visibility'] === 'private');
            $result = $client->createRepository($name, $isPrivate);
            echo json_encode(['status' => 'success', 'message' => "Repository '$name' created on GitHub."]);
            break;

        case 'push':
            $message = $_POST['message'] ?: "AI Auto-update " . date('Y-m-d H:i');
            $branch = $_POST['branch'] ?: 'main';
            
            $result = $client->pushAllFiles($message, $branch, PROJECT_ROOT);
            echo json_encode(['status' => 'success', 'message' => "Pushed successfully to $branch branch."]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid git action.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
