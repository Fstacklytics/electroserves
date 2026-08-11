@props([
    'name',
    'label',
    'options' => [],
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helpText' => null,
])

@php
    /**
     * Select control.
     *
     * `options` is an associative array of value => label.
     */
    $id = $id ?? 'select-'.\Illuminate\Support\Str::slug($name).'-'.\Illuminate\Support\Str::random(4);
    // $errors is only shared by the session middleware, so a component
    // rendered outside a request context (tests, a mail view) must not assume
    // it exists. An explicitly passed `error` prop always wins.
    $bag = $errors ?? session('errors');
    $error = $error ?? ($bag instanceof \Illuminate\Support\ViewErrorBag || $bag instanceof \Illuminate\Support\MessageBag
        ? $bag->first($name)
        : null);
    $hasError = filled($error);
    $selected = old($name, $value);

    $describedBy = array_filter([
        $helpText ? $id.'-help' : null,
        $hasError ? $id.'-error' : null,
    ]);
@endphp

<div class="w-full">
    <label for="{{ $id }}" class="block text-sm font-medium text-neutral-900">
        {{ $label }}
        @if ($required)
            <span class="text-danger-600" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        @endif
    </label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($disabled) disabled aria-disabled="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes->merge([
            'class' => 'mt-1.5 block w-full min-h-touch rounded-md border px-3 py-2.5 text-base shadow-sm transition-colors '
                .'focus:outline-none focus:ring-2 '
                .'disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500 '
                .($hasError
                    ? 'border-danger-500 text-danger-900 focus:border-danger-600 focus:ring-danger-500'
                    : 'border-neutral-300 text-neutral-900 focus:border-primary-600 focus:ring-primary-500'),
        ]) }}
    >
        @if ($placeholder)
            <option value="" @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($helpText)
        <p id="{{ $id }}-help" class="mt-1.5 text-sm text-neutral-500">{{ $helpText }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-sm font-medium text-danger-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
