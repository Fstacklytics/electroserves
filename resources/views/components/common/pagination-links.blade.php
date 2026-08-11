@if ($paginator->hasPages())
    <nav aria-label="{{ __('common.pagination.label') }}" class="mt-10 flex justify-center">
        <ul role="list" class="flex flex-wrap items-center gap-1">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span
                        class="flex min-h-touch cursor-not-allowed items-center rounded-md px-3 text-sm text-neutral-400"
                        aria-disabled="true"
                    >{{ __('common.pagination.previous') }}</span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        data-pagination-link
                        rel="prev"
                        class="flex min-h-touch items-center rounded-md px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                    >{{ __('common.pagination.previous') }}</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="flex min-h-touch items-center px-2 text-sm text-neutral-400" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span
                                    class="flex min-h-touch min-w-touch items-center justify-center rounded-md bg-primary-800 px-3 text-sm font-semibold text-white"
                                    aria-current="page"
                                >
                                    <span class="sr-only">{{ __('common.pagination.current_page', ['page' => $page]) }}</span>
                                    <span aria-hidden="true">{{ $page }}</span>
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    data-pagination-link
                                    class="flex min-h-touch min-w-touch items-center justify-center rounded-md px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                                >
                                    <span class="sr-only">{{ __('common.pagination.go_to_page', ['page' => $page]) }}</span>
                                    <span aria-hidden="true">{{ $page }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        data-pagination-link
                        rel="next"
                        class="flex min-h-touch items-center rounded-md px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                    >{{ __('common.pagination.next') }}</a>
                @else
                    <span
                        class="flex min-h-touch cursor-not-allowed items-center rounded-md px-3 text-sm text-neutral-400"
                        aria-disabled="true"
                    >{{ __('common.pagination.next') }}</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
