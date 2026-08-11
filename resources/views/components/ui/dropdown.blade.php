@props([
    'trigger' => null,
    'label' => null,
    'align' => 'left',
    'width' => 'w-56',
])

@php
    /**
     * Accessible dropdown menu.
     *
     * Keyboard: Enter/Space toggles, Arrow keys move between items, Escape
     * closes and returns focus to the trigger, and moving focus out of the
     * component closes it.
     */
    $alignment = $align === 'right' ? 'right-0' : 'left-0';
@endphp

<div x-data="dropdown()" {{ $attributes->merge(['class' => 'relative inline-block text-left']) }}>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="focusNext()"
        x-on:keydown.arrow-up.prevent="focusPrevious()"
        x-on:keydown.escape.prevent="close(true)"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="true"
        class="inline-flex min-h-touch items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100"
    >
        <span>{{ $trigger ?? $label ?? __('common.cta.learn_more') }}</span>
        <svg
            class="h-4 w-4 shrink-0 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
            focusable="false"
        >
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-ref="menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="menu"
        aria-orientation="vertical"
        class="absolute z-30 mt-2 {{ $alignment }} {{ $width }} origin-top overflow-hidden rounded-md border border-neutral-200 bg-white py-1 shadow-lg"
    >
        {{ $slot }}
    </div>
</div>
