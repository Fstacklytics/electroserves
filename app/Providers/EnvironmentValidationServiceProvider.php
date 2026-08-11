<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\MissingEnvironmentException;
use Illuminate\Support\ServiceProvider;

/**
 * Fails fast when the environment is not fully configured.
 *
 * A missing MAIL_* or CONTACT_NOTIFICATION_EMAIL value would otherwise surface
 * as a silent failure the first time a visitor submits the contact form. Boot
 * time is the correct place to detect it.
 */
class EnvironmentValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Console commands must stay runnable so `key:generate` and friends can
        // be used to fix an incomplete .env in the first place.
        if ($this->app->runningInConsole()) {
            return;
        }

        $missing = $this->missingVariables();

        if ($missing !== []) {
            throw MissingEnvironmentException::forVariables($missing);
        }
    }

    /**
     * Names of required variables that are absent or blank.
     *
     * @return list<string>
     */
    public function missingVariables(): array
    {
        /** @var list<string> $required */
        $required = (array) config('electroserves.required_env', []);

        if (strtolower((string) env('MAIL_MAILER')) === 'smtp') {
            /** @var list<string> $smtpOnly */
            $smtpOnly = (array) config('electroserves.required_env_smtp', []);
            $required = array_merge($required, $smtpOnly);
        }

        $missing = [];

        foreach (array_unique($required) as $name) {
            $value = env($name);

            if ($value === null || $value === '' || $value === false) {
                $missing[] = $name;
            }
        }

        return $missing;
    }
}
