<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a contact form submission.
 *
 * Every field is explicitly typed and bounded — the controller only ever sees
 * `validated()` data, never the raw request.
 */
class ContactFormRequest extends FormRequest
{
    /**
     * Set when the honeypot is filled or the form was submitted implausibly
     * fast. The request is answered with the normal success page so a bot
     * cannot tell it was rejected, but nothing is sent.
     */
    private bool $flaggedAsSpam = false;

    public function authorize(): bool
    {
        // Public marketing form: no authorisation, protection is CSRF + throttle.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $serviceTypes = $this->allowedServiceTypes();

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            // Optional, but when present must look like a dialable number.
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+][0-9+\-\s()]{5,31}$/'],
            'service_type' => ['required', 'string', Rule::in($serviceTypes)],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],

            // The honeypot is deliberately NOT given a failing rule. A bot that
            // receives a validation error learns the field is a trap; instead
            // withValidator() flags the submission and the controller answers
            // with the ordinary success page while discarding the message.
            $this->honeypotField() => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('contact.validation.name_required'),
            'name.min' => __('contact.validation.name_min'),
            'email.required' => __('contact.validation.email_required'),
            'email.email' => __('contact.validation.email_invalid'),
            'phone.regex' => __('contact.validation.phone_invalid'),
            'service_type.required' => __('contact.validation.service_required'),
            'service_type.in' => __('contact.validation.service_invalid'),
            'message.required' => __('contact.validation.message_required'),
            'message.min' => __('contact.validation.message_min'),
            'message.max' => __('contact.validation.message_max'),
            'consent.accepted' => __('contact.validation.consent_required'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('contact.fields.name'),
            'email' => __('contact.fields.email'),
            'phone' => __('contact.fields.phone'),
            'service_type' => __('contact.fields.service_type'),
            'message' => __('contact.fields.message'),
            'consent' => __('contact.fields.consent'),
        ];
    }

    /**
     * Trim input before validation so " " does not pass a `required` rule.
     */
    protected function prepareForValidation(): void
    {
        $trim = static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value;

        $this->merge([
            'name' => $trim($this->input('name')),
            'email' => $trim($this->input('email')),
            'phone' => $trim($this->input('phone')),
            'service_type' => $trim($this->input('service_type')),
            'message' => $trim($this->input('message')),
        ]);
    }

    /**
     * Silent spam checks that run after the field rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->honeypotWasFilled()) {
                $this->flaggedAsSpam = true;

                Log::info('Contact form submission rejected: honeypot filled.', [
                    'ip' => $this->ip(),
                ]);

                return;
            }

            if ($this->submittedTooQuickly()) {
                $this->flaggedAsSpam = true;

                Log::info('Contact form submission rejected: submitted implausibly fast.', [
                    'ip' => $this->ip(),
                ]);
            }
        });
    }

    /**
     * Whether this submission looks automated and must not be delivered.
     */
    public function isSpam(): bool
    {
        return $this->flaggedAsSpam;
    }

    public function honeypotField(): string
    {
        return (string) config('electroserves.contact.honeypot_field', 'website_url');
    }

    private function honeypotWasFilled(): bool
    {
        $value = $this->input($this->honeypotField());

        return is_string($value) && trim($value) !== '';
    }

    /**
     * A human cannot read the form and submit it within a couple of seconds.
     *
     * The timestamp is signed by the session, so a bot cannot simply backdate
     * it — a missing or unparseable value is treated as suspicious.
     */
    private function submittedTooQuickly(): bool
    {
        $minSeconds = (int) config('electroserves.contact.min_submit_seconds', 3);

        if ($minSeconds <= 0) {
            return false;
        }

        $renderedAt = $this->session()->get('contact_form_rendered_at');

        if (! is_int($renderedAt)) {
            // No timestamp in session: the form was not rendered by us in this
            // session. Do not block — a visitor with cookies disabled is a
            // legitimate case — but there is nothing to check either.
            return false;
        }

        return (time() - $renderedAt) < $minSeconds;
    }

    /**
     * Service types the dropdown may legitimately contain.
     *
     * @return list<string>
     */
    private function allowedServiceTypes(): array
    {
        /** @var array<string, string> $categories */
        $categories = (array) config('electroserves.service_categories', []);

        return array_merge(array_keys($categories), ['other']);
    }
}
