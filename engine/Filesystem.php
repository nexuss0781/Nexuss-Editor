<?php

namespace Engine;

/**
 * Class Filesystem
 * Handles physical file operations, directory scaffolding, and smart code injection.
 */
class Filesystem
{
    private string $root;
    private array $logs = [];

    public function __construct(string $projectRoot)
    {
        // Ensure root is absolute and exists
        $this->root = rtrim(realpath($projectRoot), DIRECTORY_SEPARATOR);
    }

    /**
     * Executes a list of parsed tasks
     */
    public function executeTasks(array $tasks): array
    {
        foreach ($tasks as $task) {
            try {
                switch ($task['action']) {
                    case 'File Creation':
                        $this->createFile($task['file'], $task['code']);
                        break;
                    case 'File Modification':
                        if ($task['type'] === 'Overwrite') {
                            $this->createFile($task['file'], $task['code']); // Overwrite is same as create
                        } elseif ($task['type'] === 'Replacement') {
                            $this->replaceFunction($task['file'], $task['function'], $task['code']);
                        }
                        break;
                }
            } catch (\Exception $e) {
                $this->log("ERROR", $e->getMessage());
            }
        }
        return $this->logs;
    }

    /**
     * Creates folders recursively and writes the file
     */
    private function createFile(string $filePath, string $content): void
    {
        $fullPath = $this->resolvePath($filePath);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Failed to create directory: $directory");
            }
            $this->log("DIR", "Created structure: " . str_replace($this->root, '', $directory));
        }

        if (file_put_contents($fullPath, $content) !== false) {
            $this->log("FILE", "Successfully written: $filePath");
        } else {
            throw new \Exception("Failed to write file: $filePath");
        }
    }

    /**
     * Smart Function Replacement
     * Uses Regex to locate a function block and swap it out
     */
    private function replaceFunction(string $filePath, string $functionName, string $newCode): void
    {
        $fullPath = $this->resolvePath($filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Target file not found for replacement: $filePath");
        }

        $content = file_get_contents($fullPath);

        /**
         * Smart Regex: Matches standard function patterns (JS/PHP/Python)
         * Matches: function name(...) { ... } OR name(...) { ... }
         * This handles curly brace blocks.
         */
        $pattern = '/(function\s+' . preg_quote($functionName) . '\s*\(.*?\)\s*\{)(.*?)(\}\n?)/s';

        if (preg_match($pattern, $content)) {
            // Replace the body while keeping the signature for safety, or replace whole block
            // Here we replace the whole block found to match the "Replacement" intent
            $newContent = preg_replace($pattern, $newCode, $content);
            
            if (file_put_contents($fullPath, $newContent)) {
                $this->log("REPLACE", "Injected function '$functionName' into $filePath");
            }
        } else {
            throw new \Exception("Could not locate function '$functionName' in $filePath. Ensure syntax matches 'function name() { }'");
        }
    }

    /**
     * Resolve and Sanitize Paths
     * Prevents Directory Traversal attacks (../)
     */
    private function resolvePath(string $path): string
    {
        // Normalize path separators
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $fullPath = $this->root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        
        return $fullPath;
    }

    private function log(string $type, string $message): void
    {
        $this->logs[] = [
            'timestamp' => date('H:i:s'),
            'type' => $type,
            'message' => $message
        ];
    }
}
