<?php

namespace Engine;

/**
 * Class Parser
 * Enterprise-grade string processor for AI-generated code instructions.
 * Responsible for converting raw text into structured executable tasks.
 */
class Parser
{
    // Define standard constants for internal logic
    const ACTION_CREATE = 'File Creation';
    const ACTION_MODIFY = 'File Modification';
    const TYPE_OVERWRITE = 'Overwrite';
    const TYPE_REPLACEMENT = 'Replacement';

    /**
     * Parses the raw content and returns an array of instructions.
     * 
     * @param string $content The raw AI response text
     * @return array List of structured tasks
     */
    public function parse(string $content): array
    {
        $tasks = [];

        // Pattern 1: File Creation
        // Action: File Creation \n File Name: path \n ```code```
        $createPattern = '/Action:\s*'.self::ACTION_CREATE.'\s*File Name:\s*([^\n]+)\s*```(.*?)```/is';
        
        // Pattern 2: File Modification (Overwrite)
        // Action: File Modification \n Type: Overwrite \n File Name: path \n ```code```
        $overwritePattern = '/Action:\s*'.self::ACTION_MODIFY.'\s*Type:\s*'.self::TYPE_OVERWRITE.'\s*File Name:\s*([^\n]+)\s*```(.*?)```/is';

        // Pattern 3: File Modification (Replacement)
        // Action: File Modification \n Type: Replacement \n Function name: name \n File name: path \n ```code```
        $replacePattern = '/Action:\s*'.self::ACTION_MODIFY.'\s*Type:\s*'.self::TYPE_REPLACEMENT.'\s*Function name:\s*([^\n]+)\s*File name:\s*([^\n]+)\s*```(.*?)```/is';

        // Extract File Creations
        if (preg_match_all($createPattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tasks[] = [
                    'action'   => self::ACTION_CREATE,
                    'file'     => trim($match[1]),
                    'code'     => trim($match[2]),
                    'function' => null
                ];
            }
        }

        // Extract Overwrites
        if (preg_match_all($overwritePattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tasks[] = [
                    'action'   => self::ACTION_MODIFY,
                    'type'     => self::TYPE_OVERWRITE,
                    'file'     => trim($match[1]),
                    'code'     => trim($match[2]),
                    'function' => null
                ];
            }
        }

        // Extract Function Replacements
        if (preg_match_all($replacePattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tasks[] = [
                    'action'   => self::ACTION_MODIFY,
                    'type'     => self::TYPE_REPLACEMENT,
                    'function' => trim($match[1]),
                    'file'     => trim($match[2]),
                    'code'     => trim($match[3])
                ];
            }
        }

        return $tasks;
    }

    /**
     * Helper to validate if the structure contains the minimum required fields
     */
    public function isValidTask(array $task): bool
    {
        if (!isset($task['action'], $task['file'], $task['code'])) {
            return false;
        }
        
        if ($task['action'] === self::ACTION_MODIFY && !isset($task['type'])) {
            return false;
        }

        return true;
    }
}
