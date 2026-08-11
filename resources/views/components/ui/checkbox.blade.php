@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'helpText' => null,
])

@php
    /**
     * Checkbox with an associated label.
     *
     * States: default, hover, focus, checked, disabled, error.
     *
     * The label is a sibling rather than a wrapper so the 44px hit area comes
     * from the label's padding while the input keeps its native semantics.
     */
    $id = $id ?? 'checkbox-'.\Illuminate\Support\Str::slug($name).'-'.\Illuminate\Support\Str::random(4);
    // $errors is only shared by the session middleware, so a component
    // rendered outside a request context (tests, a mail view) must not assume
    // it exists. An explicitly passed `error` prop always wins.
    $bag = $errors ?? session('errors');
    $error = $error ?? ($bag instanceof \Illuminate\Support\ViewErrorBag || $bag instanceof \Illuminate\Support\MessageBag
        ? $bag->first($name)
        : null);
    $hasError = filled($error);

    $describedBy = array_filter([
        $helpText ? $id.'-help' : null,
        $hasError ? $id.'-error' : null,
    ]);
@endphp

<div class="w-full">
    <div class="flex items-start gap-3">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @checked(old($name, $checked))
            @if ($required) required @endif
            @if ($disabled) disabled aria-disabled="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
            {{ $attributes->merge([
                'class' => 'mt-1 h-5 w-5 shrink-0 rounded border-2 text-primary-700 transition-colors '
                    .'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 '
                    .'disabled:cursor-not-allowed disabled:bg-neutral-100 '
                    .($hasError ? 'border-danger-500' : 'border-neutral-300'),
            ]) }}
        >

        <label
            for="{{ $id }}"
            @class([
                'text-sm leading-relaxed',
                'text-neutral-700' => ! $disabled,
                'cursor-not-allowed text-neutral-500' => $disabled,
            ])
        >
            {{ $label }}
            @if ($required)
                <span class="text-danger-600" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            @endif
        </label>
    </div>

    @if ($helpText)
        <p id="{{ $id }}-help" class="mt-1.5 pl-8 text-sm text-neutral-500">{{ $helpText }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 pl-8 text-sm font-medium text-danger-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
