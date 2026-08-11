<?php

declare(strict_types=1);

namespace App\DataObjects\Concerns;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\Log;

/**
 * Date parsing shared by content DataObjects.
 *
 * YAML dates arrive in three shapes depending on how the author quoted them:
 *   date: 2026-01-12     → int (Symfony resolves it to a Unix timestamp)
 *   date: "2026-01-12"   → string
 *   date: <parsed>       → DateTimeInterface, when a custom flag is used
 *
 * All three are accepted; anything else is logged and treated as absent.
 */
trait ParsesDates
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected static function date(array $data, string $key, string $context, bool $warnWhenMissing = false): ?DateTimeImmutable
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            if ($warnWhenMissing) {
                Log::warning('Content entry has no date; it will sort last.', [
                    'context' => $context,
                    'field' => $key,
                ]);
            }

            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        // Symfony's YAML parser resolves an unquoted ISO date to a timestamp.
        if (is_int($value)) {
            return (new DateTimeImmutable('@'.$value))->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        if (! is_string($value)) {
            Log::warning('Content entry has a date of an unexpected type; ignoring it.', [
                'context' => $context,
                'field' => $key,
                'type' => get_debug_type($value),
            ]);

            return null;
        }

        try {
            return new DateTimeImmutable(trim($value));
        } catch (\Exception $e) {
            Log::warning('Content entry has an unparseable date; ignoring it.', [
                'context' => $context,
                'field' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
