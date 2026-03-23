<?php

namespace Webkul\PluginManager\Traits;

trait ResolvesProcessBinaries
{
    protected static function getPhpExecutablePath(): string
    {
        $phpPath = trim(shell_exec('which php 2>/dev/null') ?: '');

        if (
            $phpPath
            && file_exists($phpPath)
        ) {
            return $phpPath;
        }

        $phpPath = PHP_BINARY;

        if (strpos($phpPath, 'fpm') !== false) {
            $phpPath = str_replace('fpm', '', $phpPath);
        }

        if (file_exists($phpPath)) {
            return $phpPath;
        }

        $commonPaths = [
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/opt/homebrew/bin/php',
            '/Users/'.get_current_user().'/Library/Application Support/Herd/bin/php',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'php';
    }

    protected static function buildTimeoutCommand(int $seconds, string $command): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $command;
        }

        $timeoutBin = self::findTimeoutBinary();

        if (! $timeoutBin) {
            return $command;
        }

        return "{$timeoutBin} {$seconds} {$command}";
    }

    protected static function findTimeoutBinary(): ?string
    {
        $candidates = [
            'timeout',
            'gtimeout',
            '/opt/homebrew/bin/timeout',
            '/opt/homebrew/bin/gtimeout',
            '/usr/local/bin/timeout',
            '/usr/local/bin/gtimeout',
        ];

        foreach ($candidates as $candidate) {
            $path = trim(shell_exec("which {$candidate} 2>/dev/null") ?: '');

            if ($path && file_exists($path)) {
                return $path;
            }

            if (str_starts_with($candidate, '/') && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
