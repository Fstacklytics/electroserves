<x-layouts.app :seo="$seo">
    @php
        $variants = ['primary', 'secondary', 'outline', 'ghost', 'danger'];
        $sizes = ['sm', 'md', 'lg'];
        $alertTypes = ['success', 'warning', 'error', 'info'];
    @endphp

    <x-sections.page-header
        :title="__('styleguide.title')"
        :subtitle="__('styleguide.description')"
        :breadcrumbs="[
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('styleguide.title'), 'url' => null],
        ]"
    />

    <div class="container-page space-y-16 py-12">
        <x-ui.alert type="info">{{ __('styleguide.note') }}</x-ui.alert>

        {{-- Colours --}}
        <section aria-labelledby="sg-colors">
            <h2 id="sg-colors" class="text-2xl font-bold text-neutral-900">Colour tokens</h2>
            <div class="mt-6 space-y-6">
                @foreach (['primary', 'secondary', 'neutral', 'success', 'warning', 'danger'] as $palette)
                    <div>
                        <p class="text-sm font-semibold capitalize text-neutral-700">{{ $palette }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ([50, 100, 500, 600, 700, 800, 900] as $shade)
                                <div class="text-center">
                                    <div class="h-14 w-20 rounded-md border border-neutral-200 bg-{{ $palette }}-{{ $shade }}"></div>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $shade }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Typography --}}
        <section aria-labelledby="sg-type">
            <h2 id="sg-type" class="text-2xl font-bold text-neutral-900">Typography</h2>
            <div class="mt-6 space-y-3">
                <p class="text-5xl font-bold text-neutral-900">Display 5xl</p>
                <p class="text-4xl font-bold text-neutral-900">Heading 4xl</p>
                <p class="text-3xl font-semibold text-neutral-900">Heading 3xl</p>
                <p class="text-2xl font-semibold text-neutral-900">Heading 2xl</p>
                <p class="text-xl font-semibold text-neutral-900">Heading xl</p>
                <p class="text-base">Body base — the quick brown fox jumps over the lazy dog.</p>
                <p class="text-sm text-neutral-600">Small — supporting copy and help text.</p>
                <p class="font-mono text-sm">Mono — JetBrains Mono 0123456789</p>
            </div>
        </section>

        {{-- Buttons: every variant in every state --}}
        <section aria-labelledby="sg-buttons">
            <h2 id="sg-buttons" class="text-2xl font-bold text-neutral-900">Buttons</h2>

            <div class="mt-6 space-y-6">
                @foreach ($variants as $variant)
                    <div>
                        <p class="text-sm font-semibold capitalize text-neutral-700">{{ $variant }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @foreach ($sizes as $size)
                                <x-ui.button :variant="$variant" :size="$size">{{ ucfirst($size) }}</x-ui.button>
                            @endforeach
                            <x-ui.button :variant="$variant" disabled>Disabled</x-ui.button>
                            <x-ui.button :variant="$variant" loading>Loading</x-ui.button>
                            <x-ui.button :variant="$variant" href="#">As link</x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Form controls in every state --}}
        <section aria-labelledby="sg-forms">
            <h2 id="sg-forms" class="text-2xl font-bold text-neutral-900">Form controls</h2>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-ui.input name="sg_default" label="Default input" placeholder="Placeholder text" />
                <x-ui.input name="sg_help" label="With help text" help-text="This explains what to enter." />
                <x-ui.input name="sg_error" label="Error state" error="Please enter a valid value." value="Bad value" />
                <x-ui.input name="sg_disabled" label="Disabled" value="Cannot edit" disabled />
                <x-ui.input name="sg_required" label="Required" required placeholder="Required field" />
                <x-ui.input name="sg_readonly" label="Read only" value="Read-only value" readonly />

                <x-ui.select
                    name="sg_select"
                    label="Select"
                    placeholder="Choose one"
                    :options="['residential' => 'Residential', 'commercial' => 'Commercial']"
                />
                <x-ui.select
                    name="sg_select_error"
                    label="Select with error"
                    placeholder="Choose one"
                    error="Please choose an option."
                    :options="['residential' => 'Residential']"
                />

                <div class="sm:col-span-2">
                    <x-ui.textarea name="sg_textarea" label="Textarea" placeholder="Multi-line input" help-text="Up to 5,000 characters." />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.textarea name="sg_textarea_error" label="Textarea with error" error="This field is required." />
                </div>
            </div>
        </section>

        {{-- Alerts --}}
        <section aria-labelledby="sg-alerts">
            <h2 id="sg-alerts" class="text-2xl font-bold text-neutral-900">Alerts</h2>
            <div class="mt-6 space-y-4">
                @foreach ($alertTypes as $type)
                    <x-ui.alert :type="$type" :title="ucfirst($type)">
                        This is a {{ $type }} message with a clear next step.
                    </x-ui.alert>
                @endforeach
                <x-ui.alert type="info" title="Dismissible" dismissible>
                    This alert can be dismissed.
                </x-ui.alert>
            </div>
        </section>

        {{-- Loading states --}}
        <section aria-labelledby="sg-loading">
            <h2 id="sg-loading" class="text-2xl font-bold text-neutral-900">Loading &amp; skeletons</h2>
            <div class="mt-6 flex flex-wrap items-center gap-8">
                <x-ui.spinner size="sm" />
                <x-ui.spinner size="md" />
                <x-ui.spinner size="lg" />
            </div>
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-ui.skeleton :lines="4" />
                <x-ui.skeleton height="h-32" rounded="rounded-lg" />
            </div>
        </section>

        {{-- Cards & badges --}}
        <section aria-labelledby="sg-cards">
            <h2 id="sg-cards" class="text-2xl font-bold text-neutral-900">Cards &amp; badges</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-3">
                <x-ui.card>
                    <p class="font-semibold text-neutral-900">Default card</p>
                    <p class="mt-1 text-sm text-neutral-600">Resting state.</p>
                </x-ui.card>
                <x-ui.card hoverable>
                    <p class="font-semibold text-neutral-900">Hoverable card</p>
                    <p class="mt-1 text-sm text-neutral-600">Lifts on hover and focus.</p>
                </x-ui.card>
                <x-ui.card>
                    <x-slot:header><p class="font-semibold text-neutral-900">With header</p></x-slot:header>
                    <p class="text-sm text-neutral-600">Body content.</p>
                    <x-slot:footer><p class="text-sm text-neutral-500">Footer content.</p></x-slot:footer>
                </x-ui.card>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach (['neutral', 'primary', 'secondary', 'success', 'warning', 'danger'] as $variant)
                    <x-ui.badge :variant="$variant">{{ ucfirst($variant) }}</x-ui.badge>
                @endforeach
            </div>
        </section>

        {{-- Empty states --}}
        <section aria-labelledby="sg-empty">
            <h2 id="sg-empty" class="text-2xl font-bold text-neutral-900">Empty states</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-ui.empty-state message="Nothing here yet — content will appear once published." />
                <x-ui.empty-state
                    title="No results"
                    message="No items match the current filter."
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            </div>
        </section>

        {{-- Star rating --}}
        <section aria-labelledby="sg-rating">
            <h2 id="sg-rating" class="text-2xl font-bold text-neutral-900">Star rating</h2>
            <div class="mt-6 space-y-2">
                @for ($i = 5; $i >= 1; $i--)
                    <x-ui.star-rating :rating="$i" />
                @endfor
            </div>
        </section>

        {{-- Alpine components --}}
        <section aria-labelledby="sg-interactive">
            <h2 id="sg-interactive" class="text-2xl font-bold text-neutral-900">Interactive components</h2>

            <div class="mt-6 grid gap-8 sm:grid-cols-2">
                {{-- Dropdown --}}
                <div x-data="dropdown()" class="relative">
                    <p class="mb-2 text-sm font-semibold text-neutral-700">Dropdown</p>
                    <button
                        type="button"
                        x-ref="trigger"
                        x-on:click="toggle()"
                        x-on:keydown.arrow-down.prevent="focusNext()"
                        x-on:keydown.arrow-up.prevent="focusPrevious()"
                        x-on:keydown.escape.prevent="close(true)"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-haspopup="true"
                        class="min-h-touch rounded-md border border-neutral-300 px-4 text-sm font-medium hover:bg-neutral-100"
                    >Open menu</button>

                    <div
                        x-ref="menu"
                        x-show="open"
                        x-cloak
                        role="menu"
                        class="absolute z-10 mt-2 w-48 rounded-md border border-neutral-200 bg-white py-1 shadow-lg"
                    >
                        @foreach (['First item', 'Second item', 'Third item'] as $item)
                            <button type="button" role="menuitem" x-on:click="onSelect()" class="block w-full px-4 py-2.5 text-left text-sm hover:bg-neutral-100">{{ $item }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Modal --}}
                <div x-data="modal()">
                    <p class="mb-2 text-sm font-semibold text-neutral-700">Modal</p>
                    <x-ui.button variant="outline" size="md" x-on:click="show()">Open modal</x-ui.button>

                    <div
                        x-show="open"
                        x-cloak
                        x-trap.noscroll="open"
                        x-on:keydown.escape.window="close()"
                        class="fixed inset-0 z-[70] flex items-center justify-center bg-neutral-950/60 p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="sg-modal-title"
                    >
                        <div x-on:click.outside="close()" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h3 id="sg-modal-title" class="text-lg font-semibold text-neutral-900">Example dialog</h3>
                            <p class="mt-2 text-sm text-neutral-600">Focus is trapped here and returns to the trigger on close.</p>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-ui.button variant="ghost" size="sm" x-on:click="close()">Cancel</x-ui.button>
                                <x-ui.button variant="primary" size="sm" x-on:click="close()">Confirm</x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Accordion --}}
                <div>
                    <p class="mb-2 text-sm font-semibold text-neutral-700">Accordion</p>
                    <div x-data="accordion({ multiple: false })" class="divide-y divide-neutral-200 rounded-lg border border-neutral-200">
                        @foreach (['one', 'two'] as $key)
                            <div>
                                <h3>
                                    <button
                                        type="button"
                                        data-accordion-header
                                        :aria-expanded="isOpen(@js($key)) ? 'true' : 'false'"
                                        aria-controls="sg-panel-{{ $key }}"
                                        x-on:click="toggle(@js($key))"
                                        x-on:keydown.arrow-down.prevent="moveFocus(1, $event.target)"
                                        x-on:keydown.arrow-up.prevent="moveFocus(-1, $event.target)"
                                        class="flex w-full min-h-touch items-center justify-between px-4 text-left text-sm font-medium hover:bg-neutral-50"
                                    >Section {{ $key }}</button>
                                </h3>
                                <div id="sg-panel-{{ $key }}" x-show="isOpen(@js($key))" x-cloak x-collapse>
                                    <p class="px-4 pb-4 text-sm text-neutral-600">Panel content for section {{ $key }}.</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Toast --}}
                <div>
                    <p class="mb-2 text-sm font-semibold text-neutral-700">Toast</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['info', 'success', 'error'] as $type)
                            <x-ui.button
                                variant="outline"
                                size="sm"
                                x-on:click="window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'This is a {{ $type }} toast', type: '{{ $type }}' } }))"
                            >{{ ucfirst($type) }}</x-ui.button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
