@props(['testimonial'])

<x-ui.card as="figure" class="flex h-full flex-col" data-service="{{ $testimonial->serviceUsed }}">
    <x-ui.star-rating :rating="$testimonial->rating" :label="$testimonial->ratingLabel()" />

    <blockquote class="mt-3 flex-1">
        <p class="text-base leading-relaxed text-neutral-700">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
    </blockquote>

    <figcaption class="mt-5 flex items-center gap-3 border-t border-neutral-200 pt-4">
        @if ($testimonial->hasPhoto())
            <x-ui.media
                :src="$testimonial->photo"
                :alt="__('testimonials.photo_alt', ['name' => $testimonial->clientName])"
                :width="48"
                :height="48"
                class="h-12 w-12 shrink-0 rounded-full object-cover"
            />
        @else
            {{-- Initials avatar instead of an external placeholder image. --}}
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-800" aria-hidden="true">
                {{ $testimonial->initials() }}
            </span>
        @endif

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-neutral-900">{{ $testimonial->clientName }}</p>
            @if ($testimonial->company)
                <p class="truncate text-sm text-neutral-500">{{ $testimonial->company }}</p>
            @endif
        </div>
    </figcaption>
</x-ui.card>
