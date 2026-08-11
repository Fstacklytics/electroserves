<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // The limiter is backed by the cache; clear it between tests.
        RateLimiter::clear('contact-form');
        app('cache')->flush();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Amina Hassan',
            'email' => 'amina@example.com',
            'phone' => '+255 700 123 456',
            'service_type' => 'residential',
            'message' => 'I need a quote for rewiring a three-bedroom house in Masaki.',
            'consent' => '1',
        ], $overrides);
    }

    // -----------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------

    public function test_a_valid_submission_sends_the_notification_email(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('contact_status', 'success');

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
            return $mail->submission['email'] === 'amina@example.com'
                && $mail->submission['service_type'] === 'residential';
        });
    }

    public function test_the_notification_is_addressed_to_the_configured_recipient(): void
    {
        config(['electroserves.contact.notification_email' => 'enquiries@electroserves.test']);

        $this->post(route('contact.store'), $this->validPayload());

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
            return $mail->hasTo('enquiries@electroserves.test');
        });
    }

    public function test_the_visitor_address_is_used_as_reply_to_not_from(): void
    {
        $this->post(route('contact.store'), $this->validPayload());

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
            // Building the envelope proves Reply-To carries the visitor address.
            return $mail->envelope()->replyTo[0]->address === 'amina@example.com';
        });
    }

    public function test_a_submission_without_a_phone_number_is_accepted(): void
    {
        $this->post(route('contact.store'), $this->validPayload(['phone' => null]))
            ->assertSessionHas('contact_status', 'success');

        Mail::assertSent(ContactFormMail::class);
    }

    // -----------------------------------------------------------------
    // Validation failures
    // -----------------------------------------------------------------

    public function test_an_empty_submission_is_rejected_with_errors_for_every_required_field(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), []);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'email', 'service_type', 'message', 'consent']);

        Mail::assertNothingSent();
    }

    public function test_an_invalid_email_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['phone' => 'call me maybe']))
            ->assertSessionHasErrors('phone');

        Mail::assertNothingSent();
    }

    public function test_a_service_type_outside_the_allowed_list_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['service_type' => 'time-travel']))
            ->assertSessionHasErrors('service_type');

        Mail::assertNothingSent();
    }

    public function test_a_message_that_is_too_short_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['message' => 'hi']))
            ->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }

    public function test_a_message_that_is_too_long_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['message' => str_repeat('a', 5001)]))
            ->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }

    public function test_submission_without_consent_is_rejected(): void
    {
        $payload = $this->validPayload();
        unset($payload['consent']);

        $this->from(route('contact'))
            ->post(route('contact.store'), $payload)
            ->assertSessionHasErrors('consent');

        Mail::assertNothingSent();
    }

    public function test_whitespace_only_input_does_not_satisfy_required_rules(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), $this->validPayload(['name' => '   ', 'message' => '     ']))
            ->assertSessionHasErrors(['name', 'message']);

        Mail::assertNothingSent();
    }

    // -----------------------------------------------------------------
    // Spam controls
    // -----------------------------------------------------------------

    public function test_a_filled_honeypot_silently_discards_the_submission(): void
    {
        $field = (string) config('electroserves.contact.honeypot_field');

        $response = $this->post(route('contact.store'), $this->validPayload([
            $field => 'http://spam.example.com',
        ]));

        // The bot sees the ordinary success response...
        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('contact_status', 'success');

        // ...but nothing is delivered.
        Mail::assertNothingSent();
    }

    public function test_a_submission_faster_than_a_human_is_discarded(): void
    {
        // Simulate the form having been rendered a moment ago.
        $response = $this->withSession(['contact_form_rendered_at' => time()])
            ->post(route('contact.store'), $this->validPayload());

        $response->assertSessionHas('contact_status', 'success');

        Mail::assertNothingSent();
    }

    public function test_a_submission_after_a_plausible_delay_is_accepted(): void
    {
        $response = $this->withSession(['contact_form_rendered_at' => time() - 120])
            ->post(route('contact.store'), $this->validPayload());

        $response->assertSessionHas('contact_status', 'success');

        Mail::assertSent(ContactFormMail::class);
    }

    // -----------------------------------------------------------------
    // Rate limiting (threat model T3)
    // -----------------------------------------------------------------

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        $max = (int) config('electroserves.contact.rate_limit.max_attempts');

        for ($i = 0; $i < $max; $i++) {
            $this->post(route('contact.store'), $this->validPayload())
                ->assertSessionHas('contact_status', 'success');
        }

        // The next attempt is blocked and told why.
        $blocked = $this->post(route('contact.store'), $this->validPayload());
        $blocked->assertSessionHas('contact_status', 'rate_limited');

        Mail::assertSentCount($max);
    }

    // -----------------------------------------------------------------
    // Delivery failures
    // -----------------------------------------------------------------

    public function test_a_mail_transport_failure_shows_the_error_state_rather_than_a_500(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP connection refused'));

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('contact_status', 'error');
        // The visitor's input is returned so they do not retype it.
        $response->assertSessionHasInput('name', 'Amina Hassan');
    }

    public function test_a_missing_recipient_configuration_shows_the_error_state(): void
    {
        config(['electroserves.contact.notification_email' => null]);

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertSessionHas('contact_status', 'error');

        Mail::assertNothingSent();
    }

    // -----------------------------------------------------------------
    // Form rendering
    // -----------------------------------------------------------------

    public function test_the_contact_page_renders_a_csrf_protected_form_with_a_honeypot(): void
    {
        $field = (string) config('electroserves.contact.honeypot_field');

        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="'.$field.'"', false);
        // Every visible field is labelled.
        $response->assertSee(__('contact.fields.name'));
        $response->assertSee(__('contact.fields.email'));
        $response->assertSee(__('contact.fields.message'));
    }

    public function test_the_success_state_is_shown_after_a_successful_submission(): void
    {
        $this->followingRedirects()
            ->post(route('contact.store'), $this->validPayload())
            ->assertOk()
            ->assertSee(__('contact.status.success_title'));
    }

    public function test_validation_errors_are_displayed_on_the_form(): void
    {
        $this->followingRedirects()
            ->from(route('contact'))
            ->post(route('contact.store'), [])
            ->assertOk()
            ->assertSee(__('contact.validation.summary_heading'));
    }

    public function test_a_service_detail_slug_preselects_its_category_in_the_dropdown(): void
    {
        $this->get(route('contact', ['service' => 'residential-electrical']))
            ->assertOk()
            ->assertSee('value="residential" selected', false);
    }

    public function test_a_direct_category_query_still_preselects_the_dropdown(): void
    {
        $this->get(route('contact', ['service' => 'residential']))
            ->assertOk()
            ->assertSee('value="residential" selected', false);
    }

    public function test_an_unknown_service_query_leaves_the_dropdown_unselected(): void
    {
        $this->get(route('contact', ['service' => 'unknown-service']))
            ->assertOk()
            ->assertDontSee('value="residential" selected', false)
            ->assertSee('<option value="" selected', false);
    }
}
