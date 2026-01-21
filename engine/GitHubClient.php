<?php

namespace Engine;

/**
 * Class GitHubClient
 * Enterprise-grade GitHub API wrapper using cURL.
 * Handles Repository management, Branching, and Atomic Commits.
 */
class GitHubClient
{
    private string $token;
    private string $owner;
    private string $repo;
    private string $apiUrl = "https://api.github.com";

    public function __construct(string $token, string $owner, string $repo)
    {
        $this->token = $token;
        $this->owner = $owner;
        $this->repo = $repo;
    }

    /**
     * Create a new repository (Public or Private)
     */
    public function createRepository(string $name, bool $private = true): array
    {
        return $this->request("/user/repos", [
            'name' => $name,
            'private' => $private,
            'auto_init' => true
        ], 'POST');
    }

    /**
     * Get list of all branches
     */
    public function getBranches(): array
    {
        return $this->request("/repos/{$this->owner}/{$this->repo}/branches");
    }

    /**
     * Create a new branch from a base branch
     */
    public function createBranch(string $newBranch, string $fromBranch = 'main'): array
    {
        $base = $this->request("/repos/{$this->owner}/{$this->repo}/git/ref/heads/{$fromBranch}");
        $sha = $base['object']['sha'];

        return $this->request("/repos/{$this->owner}/{$this->repo}/git/refs", [
            'ref' => "refs/heads/{$newBranch}",
            'sha' => $sha
        ], 'POST');
    }

    /**
     * Atomic Sync: Stages all local files, creates a tree, commits, and pushes.
     */
    public function pushAllFiles(string $message, string $branch, string $localPath): array
    {
        // 1. Get the SHA of the latest commit on the branch
        $ref = $this->request("/repos/{$this->owner}/{$this->repo}/git/ref/heads/{$branch}");
        $lastCommitSha = $ref['object']['sha'];

        // 2. Prepare the Tree (Recursive scan of local files)
        $treeItems = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($localPath));
        
        foreach ($files as $file) {
            if ($file->isDir() || str_contains($file->getPathname(), '.git')) continue;

            $relativePath = ltrim(str_replace($localPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relativePath = str_replace('\\', '/', $relativePath); // GitHub requires forward slashes

            $treeItems[] = [
                'path' => $relativePath,
                'mode' => '100644', // Standard file mode
                'type' => 'blob',
                'content' => file_get_contents($file->getPathname())
            ];
        }

        // 3. Create a new Tree on GitHub
        $newTree = $this->request("/repos/{$this->owner}/{$this->repo}/git/trees", [
            'base_tree' => $lastCommitSha,
            'tree' => $treeItems
        ], 'POST');

        // 4. Create the Commit
        $newCommit = $this->request("/repos/{$this->owner}/{$this->repo}/git/commits", [
            'message' => $message,
            'tree' => $newTree['sha'],
            'parents' => [$lastCommitSha]
        ], 'POST');

        // 5. Update the Reference (Push)
        return $this->request("/repos/{$this->owner}/{$this->repo}/git/refs/heads/{$branch}", [
            'sha' => $newCommit['sha']
        ], 'PATCH');
    }

    /**
     * Internal cURL Request Handler
     */
    private function request(string $endpoint, array $data = null, string $method = 'GET'): array
    {
        $ch = curl_init($this->apiUrl . $endpoint);
        $headers = [
            "Authorization: token {$this->token}",
            "User-Agent: AI-Engine-App",
            "Content-Type: application/json",
            "Accept: application/vnd.github.v3+json"
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($status >= 400) {
            throw new \Exception("GitHub API Error ($status): " . ($result['message'] ?? $response));
        }

        return $result;
    }
}
