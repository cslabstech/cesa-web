<?php

namespace Cesa\Document\Services;

class PlaceholderService
{
    /**
     * Extract placeholders in the format {{$KEY}} or {{KEY}} from the given content.
     * Returns unique keys in discovery order.
     *
     * @return array<int, string>
     */
    public function extract(string $content): array
    {
        // Match both {{$KEY}} and {{KEY}} patterns, allowing optional spaces
        preg_match_all('/\{\{\s*\$?\s*([A-Z0-9_]+)\s*\}\}/i', $content, $matches);
        $keys = $matches[1] ?? [];
        // Ensure uniqueness while preserving order
        $seen = [];
        $unique = [];
        foreach ($keys as $k) {
            $k = strtoupper($k); // Normalize to uppercase
            if (! isset($seen[$k])) {
                $seen[$k] = true;
                $unique[] = $k;
            }
        }

        return $unique;
    }

    /**
     * Replace placeholders using the provided map. If a placeholder is missing,
     * it will be left as-is by default unless $strict = true, where an exception is thrown.
     *
     * @param  array<string, string|int|float|null>  $values
     */
    public function replace(string $content, array $values, bool $strict = false): string
    {
        // Get all unique placeholders (with their original format)
        preg_match_all('/\{\{\s*(\$?[A-Z0-9_]+)\s*\}\}/i', $content, $matches);
        $foundPlaceholders = $matches[0] ?? []; // Full matches like {{$KEY}} or {{KEY}}
        $foundKeys = $matches[1] ?? []; // Just the keys with or without $

        // Normalize values keys to uppercase
        $normalizedValues = [];
        foreach ($values as $k => $v) {
            $normalizedValues[strtoupper($k)] = $v;
        }

        // Replace each found placeholder
        foreach ($foundPlaceholders as $index => $placeholder) {
            $key = strtoupper(str_replace('$', '', $foundKeys[$index]));

            if (array_key_exists($key, $normalizedValues) && $normalizedValues[$key] !== null) {
                $content = str_replace($placeholder, (string) $normalizedValues[$key], $content);
            } elseif ($strict) {
                throw new \InvalidArgumentException("Missing value for placeholder: {$key}");
            }
        }

        return $content;
    }
}
