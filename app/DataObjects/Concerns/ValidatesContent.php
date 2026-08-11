<?php

declare(strict_types=1);

namespace App\DataObjects\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Shared validation helpers for content DataObjects.
 *
 * Content originates from Markdown/YAML files edited through Decap CMS, so a
 * field can always be absent, empty, or the wrong type. These helpers make the
 * "check first, log, degrade gracefully" rule cheap to follow: every accessor
 * states what it expects and records precisely what was wrong when it fails.
 */
trait ValidatesContent
{
    /**
     * Ensure every required key holds a non-empty scalar value.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $required
     */
    protected static function hasRequired(array $data, array $required, string $context): bool
    {
        $missing = [];

        foreach ($required as $key) {
            if (! array_key_exists($key, $data)) {
                $missing[] = $key;

                continue;
            }

            $value = $data[$key];

            if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            Log::warning('Content entry skipped: missing required field(s).', [
                'context' => $context,
                'missing' => $missing,
                'entry' => static::class,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Read a string field, trimming whitespace and falling back when absent.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function str(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Read an optional string field, returning null when absent or blank.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function nullableStr(array $data, string $key): ?string
    {
        $value = static::str($data, $key);

        return $value === '' ? null : $value;
    }

    /**
     * Read an integer field, clamping to an optional inclusive range.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function int(array $data, string $key, int $default = 0, ?int $min = null, ?int $max = null): int
    {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            $result = $value;
        } elseif (is_numeric($value)) {
            $result = (int) $value;
        } else {
            $result = $default;
        }

        if ($min !== null && $result < $min) {
            $result = $min;
        }

        if ($max !== null && $result > $max) {
            $result = $max;
        }

        return $result;
    }

    /**
     * Read a boolean field, accepting the YAML spellings Decap may emit.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['true', 'yes', '1', 'on'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return $default;
    }

    /**
     * Read a list of strings, tolerating both scalar lists and lists of maps.
     *
     * Decap's `list` widget with sub-fields produces `[['feature' => '...']]`,
     * while a simple list produces `['...']`. Both shapes are accepted.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected static function stringList(array $data, string $key, ?string $innerKey = null): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $trimmed = trim($item);

                if ($trimmed !== '') {
                    $out[] = $trimmed;
                }

                continue;
            }

            if (is_array($item) && $innerKey !== null && isset($item[$innerKey]) && is_string($item[$innerKey])) {
                $trimmed = trim($item[$innerKey]);

                if ($trimmed !== '') {
                    $out[] = $trimmed;
                }
            }
        }

        return $out;
    }

    /**
     * Read a list of associative rows, discarding anything that is not a map.
     *
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    protected static function mapList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Constrain a value to an allowed set, logging and falling back otherwise.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $allowed
     */
    protected static function enum(array $data, string $key, array $allowed, string $default, string $context): string
    {
        $value = static::str($data, $key);

        if ($value === '') {
            return $default;
        }

        if (! in_array($value, $allowed, true)) {
            Log::warning('Content entry has an unrecognised value; using default.', [
                'context' => $context,
                'field' => $key,
                'value' => $value,
                'allowed' => $allowed,
                'default' => $default,
            ]);

            return $default;
        }

        return $value;
    }
}
