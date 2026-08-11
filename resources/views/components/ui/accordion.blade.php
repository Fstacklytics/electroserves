@props([
    'items' => [],
    'multiple' => true,
    'initial' => null,
])

@php
    /**
     * Accessible accordion.
     *
     * `items` is a list of ['id' => string, 'heading' => string, 'content' => string|HtmlString].
     * Content is escaped unless an HtmlString is passed, so caller-sanitised
     * Markdown can be rendered while plain strings stay safe.
     *
     * Implements the WAI-ARIA accordion pattern: each header is a button
     * carrying aria-expanded and aria-controls, each panel is a labelled
     * region, and Arrow/Home/End move focus between headers.
     */
    $items = collect($items);
@endphp

<div
    x-data="accordion({ multiple: {{ $multiple ? 'true' : 'false' }}, initial: {{ $initial ? \Illuminate\Support\Js::from($initial) : 'null' }} })"
    {{ $attributes->merge(['class' => 'divide-y divide-neutral-200 overflow-hidden rounded-lg border border-neutral-200 bg-white']) }}
>
    @if ($items->isEmpty())
        {{-- Empty state: an accordion with nothing in it still explains itself. --}}
        <p class="px-5 py-6 text-center text-sm text-neutral-500">{{ __('common.states.empty') }}</p>
    @else
        @foreach ($items as $item)
            @php $itemId = $item['id']; @endphp
            <div>
                <h3>
                    <button
                        type="button"
                        data-accordion-header
                        id="{{ $itemId }}-header"
                        aria-controls="{{ $itemId }}-panel"
                        :aria-expanded="isOpen(@js($itemId)) ? 'true' : 'false'"
                        x-on:click="toggle(@js($itemId))"
                        x-on:keydown.arrow-down.prevent="moveFocus(1, $event.target)"
                        x-on:keydown.arrow-up.prevent="moveFocus(-1, $event.target)"
                        x-on:keydown.home.prevent="focusFirst()"
                        x-on:keydown.end.prevent="focusLast()"
                        class="flex min-h-touch w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-neutral-50"
                    >
                        <span class="text-base font-medium text-neutral-900">{{ $item['heading'] }}</span>
                        <svg
                            class="h-5 w-5 shrink-0 text-neutral-500 transition-transform duration-200"
                            :class="isOpen(@js($itemId)) ? 'rotate-180' : ''"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                            focusable="false"
                        >
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </h3>

                <div
                    id="{{ $itemId }}-panel"
                    role="region"
                    aria-labelledby="{{ $itemId }}-header"
                    x-show="isOpen(@js($itemId))"
                    x-cloak
                    x-collapse
                >
                    <div class="prose prose-sm prose-neutral max-w-none px-5 pb-5">
                        {{ $item['content'] }}
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
