<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use App\Services\ContentService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        $settings = $this->content->siteSettings();

        // Record when the form was rendered so the request class can reject
        // submissions that arrive faster than a human could type.
        session(['contact_form_rendered_at' => time()]);

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('contact.title'), 'url' => null],
        ];

        return view('pages.contact', [
            'settings' => $settings,
            'serviceTypes' => $this->serviceTypeOptions(),
            'honeypotField' => (string) config('electroserves.contact.honeypot_field', 'website_url'),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('contact.title'),
                'description' => __('contact.meta_description'),
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->organizationSchema($settings)),
        ]);
    }

    /**
     * Handle a contact form submission.
     *
     * Failure modes handled explicitly:
     *   - invalid input  → redirected back with per-field errors (FormRequest)
     *   - spam           → answered with the success page, nothing sent
     *   - no recipient   → logged as a configuration error, visitor told to call
     *   - mail transport → logged with context, visitor offered the phone number
     */
    public function store(ContactFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // The honeypot field is validated but must never be forwarded.
        unset($validated[$request->honeypotField()], $validated['consent']);

        if ($request->isSpam()) {
            // Mirror the success response exactly so bots learn nothing.
            return redirect()
                ->route('contact')
                ->with('contact_status', 'success');
        }

        $recipient = config('electroserves.contact.notification_email');

        if (! is_string($recipient) || trim($recipient) === '') {
            Log::error('Contact form submitted but CONTACT_NOTIFICATION_EMAIL is not configured.');

            return $this->failureResponse($request);
        }

        /** @var array{name: string, email: string, phone: ?string, service_type: string, message: string} $submission */
        $submission = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'service_type' => $validated['service_type'],
            'message' => $validated['message'],
        ];

        try {
            Mail::to($recipient)->send(new ContactFormMail(
                submission: $submission,
                serviceLabel: $this->serviceTypeOptions()[$submission['service_type']] ?? $submission['service_type'],
                submittedAt: now()->toDayDateTimeString(),
            ));
        } catch (Throwable $e) {
            // Log the failure with enough context to diagnose, but never the
            // full message body — it is visitor PII.
            Log::error('Failed to deliver contact form submission.', [
                'error' => $e->getMessage(),
                'exception' => $e::class,
                'service_type' => $submission['service_type'],
            ]);

            return $this->failureResponse($request);
        }

        Log::info('Contact form submission delivered.', [
            'service_type' => $submission['service_type'],
        ]);

        session()->forget('contact_form_rendered_at');

        return redirect()
            ->route('contact')
            ->with('contact_status', 'success');
    }

    /**
     * Send the visitor back to the form with their input and a recovery path.
     */
    private function failureResponse(ContactFormRequest $request): RedirectResponse
    {
        return redirect()
            ->route('contact')
            ->withInput($request->safe()->except([$request->honeypotField()]))
            ->with('contact_status', 'error');
    }

    /**
     * Options for the "service type" dropdown.
     *
     * @return array<string, string>
     */
    private function serviceTypeOptions(): array
    {
        /** @var array<string, string> $categories */
        $categories = (array) config('electroserves.service_categories', []);

        return $categories + ['other' => __('contact.service_other')];
    }
}
