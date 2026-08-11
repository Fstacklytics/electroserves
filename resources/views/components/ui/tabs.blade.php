@props([
    'tabs' => [],
    'initial' => null,
    'label' => null,
    'pill' => true,
])

@php
    /**
     * Accessible tab list.
     *
     * `tabs` is an associative array of value => label. The panel content is
     * supplied by the caller inside this component's Alpine scope, so the same
     * control serves both real tab panels and the client-side card filters
     * used on the index pages.
     *
     * Manual activation (WAI-ARIA): Arrow keys move focus, Enter/Space
     * activates. Without JavaScript every panel remains visible, so content is
     * never hidden from a non-JS visitor.
     */
    $tabs = collect($tabs);
    $initial = $initial ?? $tabs->keys()->first();
@endphp

<div x-data="tabs({ initial: {{ \Illuminate\Support\Js::from($initial) }} })" {{ $attributes }}>
    <div
        role="tablist"
        aria-label="{{ $label ?? __('common.tabs.label') }}"
        class="flex flex-wrap gap-2"
    >
        @foreach ($tabs as $value => $tabLabel)
            <button
                type="button"
                role="tab"
                id="tab-{{ \Illuminate\Support\Str::slug((string) $value) }}"
                aria-controls="panel-{{ \Illuminate\Support\Str::slug((string) $value) }}"
                x-on:click="select(@js($value))"
                x-on:keydown.arrow-right.prevent="moveFocus(1, $event.target)"
                x-on:keydown.arrow-left.prevent="moveFocus(-1, $event.target)"
                x-on:keydown.home.prevent="focusFirst()"
                x-on:keydown.end.prevent="focusLast()"
                :aria-selected="isActive(@js($value)) ? 'true' : 'false'"
                :tabindex="isActive(@js($value)) ? 0 : -1"
                :class="isActive(@js($value))
                    ? 'bg-primary-800 text-white border-primary-800'
                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                @class([
                    'min-h-touch border px-4 text-sm font-medium transition-colors',
                    'rounded-full' => $pill,
                    'rounded-md' => ! $pill,
                ])
            >{{ $tabLabel }}</button>
        @endforeach
    </div>

    {{ $slot }}
</div>
