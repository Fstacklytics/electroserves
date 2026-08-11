{{--
    Contact form notification.

    All values are escaped with {{ }} — the message body is visitor-supplied and
    must never be rendered as HTML.
--}}
<x-mail::message>
# {{ __('contact.email.heading') }}

{{ __('contact.email.intro') }}

**{{ __('contact.email.name') }}:** {{ $submission['name'] }}

**{{ __('contact.email.email') }}:** {{ $submission['email'] }}

**{{ __('contact.email.phone') }}:** {{ $submission['phone'] ?? __('contact.email.not_provided') }}

**{{ __('contact.email.service') }}:** {{ $serviceLabel }}

**{{ __('contact.email.submitted_at') }}:** {{ $submittedAt }}

---

**{{ __('contact.email.message') }}:**

{{ $submission['message'] }}

---

_{{ __('contact.email.reply_hint') }}_
</x-mail::message>
