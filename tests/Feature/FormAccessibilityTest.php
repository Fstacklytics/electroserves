<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Accessibility of the contact form *after a real validation failure*.
 *
 * ComponentsTest already proves the `x-ui.input` component wires `aria-invalid`
 * and `aria-describedby` when it is handed an `error` prop directly. That is a
 * unit-level guarantee about one component in isolation.
 *
 * This suite covers the part that unit tests cannot: whether a genuine rejected
 * POST — session error bag, old input, redirect, re-render — actually arrives at
 * the visitor with those attributes attached to the right fields. That path runs
 * through the form request, the error bag, the shared `$errors` view variable and
 * the component's own bag-sniffing fallback, and any one of those links can break
 * without a single component test failing.
 *
 * A screen reader was NOT used. These assertions check the markup contract that
 * assistive technology depends on; confirming how a specific screen reader
 * announces it is manual verification and is listed in deploy/README.md.
 */
class FormAccessibilityTest extends TestCase
{
    /**
     * Submit the contact form with deliberately invalid input and follow the
     * redirect back to the rendered form.
     */
    private function submitInvalid(array $overrides = []): string
    {
        $payload = array_merge([
            'name' => '',                 // required
            'email' => 'not-an-email',    // invalid format
            'phone' => '',
            'service_type' => '',         // required
            'message' => '',              // required
            'consent' => '',              // must be accepted
        ], $overrides);

        $response = $this->from(route('contact'))->post(route('contact.store'), $payload);

        $response->assertRedirect(route('contact'));

        return $this->get(route('contact'))->getContent();
    }

    public function test_invalid_input_is_rejected_rather_than_accepted(): void
    {
        // Guards the premise of every other test here.
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'service_type' => '',
                'message' => '',
                'consent' => '',
            ])
            ->assertSessionHasErrors(['name', 'email', 'service_type', 'message', 'consent']);
    }

    public function test_a_validation_summary_is_rendered_and_focusable(): void
    {
        $html = $this->submitInvalid();

        // The summary must be reachable programmatically so the visitor is not
        // left at the top of a long form with no idea what went wrong.
        $this->assertStringContainsString('id="form-errors"', $html);

        $this->assertMatchesRegularExpression(
            '/<[^>]+id="form-errors"[^>]*tabindex="-1"|<[^>]+tabindex="-1"[^>]*id="form-errors"/',
            $html,
            'The error summary must carry tabindex="-1" so it can receive focus.'
        );
    }

    public function test_the_validation_summary_uses_an_assertive_alert_role(): void
    {
        $html = $this->submitInvalid();

        $summary = $this->elementWithId($html, 'form-errors');

        $this->assertStringContainsString(
            'role="alert"',
            $summary,
            'The error summary should be announced immediately, not on next focus.'
        );
    }

    public function test_the_summary_lists_every_field_that_failed(): void
    {
        $html = $this->submitInvalid();

        $summary = $this->summaryBlock($html);

        // An empty summary box is worse than none: it implies the errors are
        // listed somewhere and leaves the visitor hunting. Assert on the
        // rendered document, which is what the visitor actually receives.
        $items = preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $summary, $matches);

        $this->assertGreaterThanOrEqual(
            4,
            $items,
            'The summary should list one item per failing field.'
        );

        $listed = strip_tags(implode(' ', $matches[1]));

        // The summary must carry the same self-describing message the field
        // itself shows, so the visitor can act on the summary alone. These
        // messages name the problem in plain language rather than prefixing an
        // attribute label, which is why we match on the message text.
        foreach ([
            __('contact.validation.name_required'),
            __('contact.validation.email_invalid'),
            __('contact.validation.service_required'),
            __('contact.validation.message_required'),
            __('contact.validation.consent_required'),
        ] as $message) {
            $this->assertStringContainsString(
                $message,
                $listed,
                'The summary is missing the message: '.$message
            );
        }
    }

    public function test_each_failing_field_is_marked_invalid(): void
    {
        $html = $this->submitInvalid();

        foreach (['name', 'service_type', 'message', 'email'] as $field) {
            $control = $this->controlNamed($html, $field);

            $this->assertStringContainsString(
                'aria-invalid="true"',
                $control,
                "The `{$field}` field failed validation but is not marked aria-invalid."
            );
        }
    }

    public function test_each_failing_field_points_at_its_own_error_message(): void
    {
        $html = $this->submitInvalid();

        foreach (['name', 'service_type', 'message', 'email'] as $field) {
            $control = $this->controlNamed($html, $field);

            preg_match('/aria-describedby="([^"]+)"/', $control, $matches);

            $this->assertNotEmpty(
                $matches,
                "The `{$field}` field has no aria-describedby, so its error is never announced."
            );

            $ids = preg_split('/\s+/', trim($matches[1])) ?: [];

            $errorIds = array_filter($ids, fn (string $id): bool => str_ends_with($id, '-error'));

            $this->assertNotEmpty(
                $errorIds,
                "The `{$field}` field's aria-describedby does not reference an error element."
            );

            // Every referenced id must exist, or the reference is dangling and
            // assistive technology announces nothing at all.
            foreach ($ids as $id) {
                $this->assertMatchesRegularExpression(
                    '/id="'.preg_quote($id, '/').'"/',
                    $html,
                    "The `{$field}` field references #{$id}, which is not present in the document."
                );
            }
        }
    }

    public function test_a_field_that_passed_validation_is_not_marked_invalid(): void
    {
        // False positives are their own accessibility failure: if everything is
        // flagged, the flag stops carrying information.
        $html = $this->submitInvalid([
            'name' => 'Asha Mwinyi',
            'email' => 'asha@example.org',
        ]);

        foreach (['name', 'email'] as $field) {
            $control = $this->controlNamed($html, $field);

            $this->assertStringNotContainsString(
                'aria-invalid="true"',
                $control,
                "The `{$field}` field passed validation but is still marked invalid."
            );
        }
    }

    public function test_contact_pii_is_deliberately_not_repopulated(): void
    {
        // Repopulating valid input is normally an accessibility win: retyping a
        // long message is a real barrier for switch, voice and screen-reader
        // users. This form deliberately trades that away.
        //
        // bootstrap/app.php declares dontFlash(['name','email','phone','message']),
        // so the visitor's name, address and enquiry are never written to the
        // session store. That is a privacy decision (Tanzania PDPA / GDPR data
        // minimisation) taken in an earlier phase, and this test pins it down so
        // nobody "fixes" the empty fields without understanding the cost.
        //
        // The mitigation is that the browser's own back/restore behaviour still
        // holds the typed values, and the summary names every field to correct.
        $html = $this->submitInvalid([
            'name' => 'Asha Mwinyi',
            'message' => 'We need a switchboard inspection at our Mikocheni warehouse.',
        ]);

        $this->assertStringNotContainsString(
            'Asha Mwinyi',
            $html,
            'Contact PII must not be flashed back into the session-rendered form.'
        );
        $this->assertStringNotContainsString('Mikocheni warehouse', $html);

        // Non-PII choices are safe to restore, and doing so is the accessible
        // behaviour for a select the visitor already answered.
        $this->assertNotContains(
            'name',
            array_keys(session()->getOldInput()),
            'The name field must not appear in the old input bag.'
        );
    }

    public function test_the_error_response_is_never_cached(): void
    {
        // A cached validation error would show one visitor another's mistakes,
        // and the page carries a CSRF token.
        $this->from(route('contact'))->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'service_type' => '',
            'message' => '',
            'consent' => '',
        ]);

        $rendered = $this->get(route('contact'));

        $cacheControl = (string) $rendered->headers->get('Cache-Control');

        $this->assertStringContainsString(
            'no-store',
            $cacheControl,
            'A page rendering validation errors must not be stored by any cache.'
        );

        $this->assertNotSame(
            'HIT',
            $rendered->headers->get('X-Response-Cache'),
            'The contact page must never be served from the response cache.'
        );
    }

    public function test_the_character_counter_is_a_polite_live_region(): void
    {
        // Polite, not assertive: a counter that interrupts on every keystroke
        // makes the field unusable with a screen reader.
        $html = $this->get(route('contact'))->getContent();

        $this->assertMatchesRegularExpression(
            '/aria-live="polite"/',
            $html,
            'The message character counter should update politely.'
        );

        $this->assertStringNotContainsString(
            'aria-live="assertive"',
            $html,
            'Nothing on the contact form should interrupt the visitor assertively except the error summary, which uses role="alert".'
        );
    }

    public function test_required_fields_are_marked_for_assistive_technology(): void
    {
        $html = $this->get(route('contact'))->getContent();

        foreach (['name', 'email', 'service_type', 'message'] as $field) {
            $control = $this->controlNamed($html, $field);

            $this->assertMatchesRegularExpression(
                '/\brequired\b|aria-required="true"/',
                $control,
                "The `{$field}` field is required but does not say so programmatically."
            );
        }
    }

    public function test_the_honeypot_field_is_hidden_from_assistive_technology(): void
    {
        // A honeypot that a screen reader reads out is a trap for the wrong
        // people: the visitor fills it in and is silently rejected as a bot.
        $html = $this->get(route('contact'))->getContent();

        $honeypot = (string) config('electroserves.contact.honeypot_field');

        $control = $this->controlNamed($html, $honeypot);

        $this->assertMatchesRegularExpression(
            '/aria-hidden="true"|tabindex="-1"/',
            $control,
            "The honeypot `{$honeypot}` must be hidden from assistive technology and removed from the tab order."
        );
    }

    /**
     * Extract the opening tag of the form control with the given `name`.
     */
    private function controlNamed(string $html, string $name): string
    {
        $pattern = '/<(?:input|textarea|select)\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>/i';

        preg_match($pattern, $html, $matches);

        $this->assertNotEmpty(
            $matches,
            "No form control named `{$name}` was found on the contact page."
        );

        return $matches[0];
    }

    /**
     * Extract the opening tag of the element carrying the given id.
     */
    private function elementWithId(string $html, string $id): string
    {
        preg_match('/<[^>]*\bid="'.preg_quote($id, '/').'"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches, "No element with id=\"{$id}\" was found.");

        return $matches[0];
    }

    /**
     * Return the markup from the error summary onwards, for list assertions.
     */
    private function summaryBlock(string $html): string
    {
        $start = strpos($html, 'id="form-errors"');

        $this->assertNotFalse($start, 'The error summary was not rendered.');

        return substr($html, $start, 2000);
    }
}
