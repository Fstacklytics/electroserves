@props([
    'name' => null,
    'title' => null,
    'size' => 'md',
    'closeLabel' => null,
])

@php
    /**
     * Accessible modal dialog.
     *
     * Usage — the trigger lives inside the same Alpine scope:
     *
     *   <div x-data="modal()">
     *       <x-ui.button x-on:click="show()">Open</x-ui.button>
     *       <x-ui.modal title="Confirm">Body</x-ui.modal>
     *   </div>
     *
     * States: closed (default) · open · with/without header and footer.
     *
     * Accessibility: role="dialog" + aria-modal, focus trapped while open and
     * returned to the trigger on close, Escape closes, background scroll
     * locked, and a click on the backdrop (but not the panel) dismisses.
     */
    $id = $name ?? 'modal-'.\Illuminate\Support\Str::random(6);
    $titleId = $id.'-title';

    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
    ];
@endphp

<div
    x-show="open"
    x-cloak
    x-trap.noscroll="open"
    x-on:keydown.escape.window="close()"
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $titleId }}" @else aria-label="{{ __('common.modal.label') }}" @endif
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-neutral-950/60"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.outside="close()"
        {{ $attributes->merge([
            'class' => 'relative w-full '.($sizes[$size] ?? $sizes['md'])
                .' max-h-[85vh] overflow-y-auto rounded-lg bg-white p-6 shadow-xl',
        ]) }}
    >
        @if ($title)
            <div class="flex items-start justify-between gap-4">
                <h2 id="{{ $titleId }}" class="text-lg font-semibold text-neutral-900">{{ $title }}</h2>

                <button
                    type="button"
                    x-on:click="close()"
                    class="touch-target -m-2 shrink-0 rounded-md text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900"
                >
                    <span class="sr-only">{{ $closeLabel ?? __('common.modal.close') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        @endif

        <div @class(['mt-4' => $title, 'text-sm text-neutral-600'])>
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
