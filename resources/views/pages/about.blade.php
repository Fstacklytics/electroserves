<x-layouts.app :seo="$seo" :schema="$schema">
    @php
        // Certifications are aggregated from the team so the section reflects
        // the qualifications actually held, with duplicates removed.
        $certifications = $team
            ->flatMap(fn ($member) => $member->certifications)
            ->unique(fn ($cert) => $cert['name'].'|'.($cert['issuer'] ?? ''))
            ->values();
    @endphp

    <x-sections.page-header
        :title="$page?->title ?? __('about.heading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($bodyHtml !== '')
                <div class="prose prose-neutral mx-auto max-w-prose">{!! $bodyHtml !!}</div>
            @else
                <x-ui.empty-state
                    :message="__('about.story_missing')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @endif
        </div>
    </section>

    {{-- Team --}}
    <section class="section-spacing bg-neutral-50" aria-labelledby="team-heading">
        <div class="container-page">
            <x-sections.section-heading
                id="team-heading"
                :title="__('about.team.heading')"
                :subtitle="__('about.team.subheading')"
            />

            @if ($team->isEmpty())
                <div class="mt-10">
                    <x-ui.empty-state :message="__('about.team.empty')" />
                </div>
            @else
                <ul role="list" class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($team as $member)
                        <li class="h-full">
                            <x-ui.card as="article" class="flex h-full flex-col text-center" padding="md">
                                @if ($member->hasPhoto())
                                    <x-ui.media
                                        :src="$member->photo"
                                        :alt="__('about.team.photo_alt', ['name' => $member->name])"
                                        :width="160"
                                        :height="160"
                                        class="mx-auto h-24 w-24 rounded-full object-cover"
                                    />
                                @else
                                    <span class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-primary-100 text-xl font-semibold text-primary-800" aria-hidden="true">
                                        {{ $member->initials() }}
                                    </span>
                                @endif

                                <h3 class="mt-4 text-base font-semibold text-neutral-900">{{ $member->name }}</h3>
                                <p class="text-sm text-primary-800">{{ $member->role }}</p>

                                @if ($member->bio !== '')
                                    <p class="mt-3 flex-1 text-sm leading-relaxed text-neutral-600">{{ \Illuminate\Support\Str::limit(strip_tags($member->bio), 160) }}</p>
                                @endif

                                @if ($member->hasCertifications())
                                    <ul role="list" class="mt-4 flex flex-wrap justify-center gap-1.5">
                                        @foreach ($member->certifications as $cert)
                                            <li><x-ui.badge variant="neutral" size="sm">{{ $cert['name'] }}</x-ui.badge></li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($member->email)
                                    <a
                                        href="mailto:{{ $member->email }}"
                                        class="mt-4 inline-flex min-h-touch items-center justify-center text-sm font-medium text-primary-800 hover:underline"
                                    >
                                        <span class="sr-only">{{ __('about.team.email_label', ['name' => $member->name]) }}</span>
                                        <span aria-hidden="true">{{ $member->email }}</span>
                                    </a>
                                @endif
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Certifications --}}
    <section class="section-spacing" aria-labelledby="certifications-heading">
        <div class="container-page">
            <x-sections.section-heading
                id="certifications-heading"
                :title="__('about.certifications.heading')"
                :subtitle="__('about.certifications.subheading')"
            />

            @if ($certifications->isEmpty())
                <div class="mt-10">
                    <x-ui.empty-state :message="__('about.certifications.empty')" :icon="false" />
                </div>
            @else
                <ul role="list" class="mx-auto mt-10 grid max-w-4xl gap-4 sm:grid-cols-2">
                    @foreach ($certifications as $cert)
                        <li>
                            <x-ui.card padding="sm" class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-success-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                    <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-neutral-900">{{ $cert['name'] }}</p>
                                    @if ($cert['issuer'] || $cert['year'])
                                        <p class="text-sm text-neutral-500">
                                            {{ collect([$cert['issuer'], $cert['year']])->filter()->implode(' · ') }}
                                        </p>
                                    @endif
                                </div>
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('home.cta.heading')"
        :body="__('home.cta.body')"
        :button-label="__('home.cta.button')"
        :button-href="route('contact')"
    />
</x-layouts.app>
