<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\MissingEnvironmentException;
use App\Providers\EnvironmentValidationServiceProvider;
use Tests\TestCase;

/**
 * The application must refuse to serve traffic when required configuration is
 * absent, rather than failing later at the point of use.
 */
class EnvironmentValidationTest extends TestCase
{
    private function provider(): EnvironmentValidationServiceProvider
    {
        return new EnvironmentValidationServiceProvider($this->app);
    }

    public function test_no_variables_are_reported_missing_in_a_configured_environment(): void
    {
        $this->assertSame([], $this->provider()->missingVariables());
    }

    public function test_a_missing_variable_is_detected(): void
    {
        // Simulate an operator who forgot the contact recipient.
        $original = $_ENV['CONTACT_NOTIFICATION_EMAIL'] ?? null;
        unset($_ENV['CONTACT_NOTIFICATION_EMAIL'], $_SERVER['CONTACT_NOTIFICATION_EMAIL']);
        putenv('CONTACT_NOTIFICATION_EMAIL');

        try {
            $this->assertContains('CONTACT_NOTIFICATION_EMAIL', $this->provider()->missingVariables());
        } finally {
            if ($original !== null) {
                $_ENV['CONTACT_NOTIFICATION_EMAIL'] = $original;
                $_SERVER['CONTACT_NOTIFICATION_EMAIL'] = $original;
                putenv('CONTACT_NOTIFICATION_EMAIL='.$original);
            }
        }
    }

    public function test_an_empty_variable_counts_as_missing(): void
    {
        $original = $_ENV['MAIL_FROM_ADDRESS'] ?? null;
        $_ENV['MAIL_FROM_ADDRESS'] = '';
        $_SERVER['MAIL_FROM_ADDRESS'] = '';
        putenv('MAIL_FROM_ADDRESS=');

        try {
            $this->assertContains('MAIL_FROM_ADDRESS', $this->provider()->missingVariables());
        } finally {
            if ($original !== null) {
                $_ENV['MAIL_FROM_ADDRESS'] = $original;
                $_SERVER['MAIL_FROM_ADDRESS'] = $original;
                putenv('MAIL_FROM_ADDRESS='.$original);
            }
        }
    }

    public function test_smtp_specific_variables_are_only_required_for_the_smtp_driver(): void
    {
        // With MAIL_MAILER=array (the test default) a mail host is unnecessary.
        $this->assertNotContains('MAIL_HOST', $this->provider()->missingVariables());

        $originalMailer = $_ENV['MAIL_MAILER'] ?? null;
        $_ENV['MAIL_MAILER'] = 'smtp';
        $_SERVER['MAIL_MAILER'] = 'smtp';
        putenv('MAIL_MAILER=smtp');

        try {
            $missing = $this->provider()->missingVariables();
            $this->assertContains('MAIL_HOST', $missing);
            $this->assertContains('MAIL_PORT', $missing);
        } finally {
            if ($originalMailer !== null) {
                $_ENV['MAIL_MAILER'] = $originalMailer;
                $_SERVER['MAIL_MAILER'] = $originalMailer;
                putenv('MAIL_MAILER='.$originalMailer);
            }
        }
    }

    public function test_the_exception_names_every_missing_variable(): void
    {
        $exception = MissingEnvironmentException::forVariables(['APP_KEY', 'MAIL_FROM_ADDRESS']);

        $this->assertStringContainsString('APP_KEY', $exception->getMessage());
        $this->assertStringContainsString('MAIL_FROM_ADDRESS', $exception->getMessage());
        // The message tells the operator how to fix it.
        $this->assertStringContainsString('.env', $exception->getMessage());
    }
}
