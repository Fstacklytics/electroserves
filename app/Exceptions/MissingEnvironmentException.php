<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown at boot when required environment variables are absent.
 *
 * The message names every missing variable so the operator can fix the whole
 * problem in one pass instead of discovering them one at a time.
 */
final class MissingEnvironmentException extends RuntimeException
{
    /**
     * @param  list<string>  $variables
     */
    public static function forVariables(array $variables): self
    {
        return new self(sprintf(
            'ElectroServes cannot start: required environment variable(s) missing or empty: %s. '
            .'Copy .env.example to .env and provide a value for each, then run `php artisan key:generate`.',
            implode(', ', $variables),
        ));
    }
}
