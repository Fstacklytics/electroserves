@props([
    'links' => [],
    'settings',
    'id' => 'mobile-menu',
])

{{--
    Mobile navigation panel.

    Rendered inside the navbar's Alpine scope, which owns the `mobileOpen`
    state — the trigger button lives next to the logo, so the two must share a
    scope rather than each holding their own.

    Accessibility:
      - focus is trapped inside the panel while it is open (x-trap) and
        returned to the hamburger button on close;
      - Escape closes it (handled on the navbar root, so it works from
        anywhere inside);
      - `.noscroll` prevents the page behind from scrolling;
      - selecting a link closes the panel so focus is not left inside a
        hidden element after navigation.
--}}
<div
    id="{{ $id }}"
    x-show="mobileOpen"
    x-cloak
    x-trap.noscroll="mobileOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => 'border-t border-neutral-200 pb-4 lg:hidden']) }}
>
    <nav aria-label="{{ __('common.nav.mobile_menu_label') }}">
        <ul role="list" class="flex flex-col py-2">
            @foreach ($links as $link)
                @php
                    $isActive = request()->is($link['pattern'])
                        || ($link['pattern'] === '/' && request()->is('/'));
                @endphp
                <li>
                    <a
                        href="{{ route($link['route']) }}"
                        @class([
                            'flex min-h-touch items-center rounded-md px-3 text-base font-medium transition-colors',
                            'bg-primary-50 text-primary-800' => $isActive,
                            'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                        x-on:click="mobileOpen = false"
                    >{{ $link['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="px-3 pt-2">
        <x-ui.button :href="route('contact')" variant="primary" size="md" class="w-full">
            {{ __('common.cta.get_quote') }}
        </x-ui.button>
    </div>

    @if ($settings->phoneHref())
        <div class="px-3 pt-3">
            <a
                href="{{ $settings->phoneHref() }}"
                class="flex min-h-touch items-center gap-2 rounded-md px-1 text-sm font-medium text-neutral-700 hover:text-primary-800"
            >
                <svg class="h-5 w-5 shrink-0 text-primary-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13V3.5Z" />
                </svg>
                <span class="sr-only">{{ __('common.meta.phone') }}:</span>
                {{ $settings->phone }}
            </a>
        </div>
    @endif
</div>
