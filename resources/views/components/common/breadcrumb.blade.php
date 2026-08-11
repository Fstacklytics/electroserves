@props(['crumbs' => []])

@if (count($crumbs) > 1)
    <nav aria-label="{{ __('common.breadcrumb.label') }}" {{ $attributes->merge(['class' => 'py-4']) }}>
        <ol class="flex flex-wrap items-center gap-1 text-sm text-neutral-500">
            @foreach ($crumbs as $index => $crumb)
                <li class="flex items-center gap-1">
                    @if ($index > 0)
                        <svg class="h-4 w-4 shrink-0 text-neutral-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                        </svg>
                    @endif

                    @if ($crumb['url'] !== null)
                        <a href="{{ $crumb['url'] }}" class="rounded py-1 hover:text-primary-800 hover:underline">{{ $crumb['label'] }}</a>
                    @else
                        <span class="py-1 font-medium text-neutral-700" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
