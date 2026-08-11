@props(['target' => 'main-content'])

{{--
    Skip-to-content link.

    Must be the first focusable element on the page. It is visually hidden until
    it receives focus (see `.skip-link` in resources/css/app.css), at which point
    it becomes a visible, high-contrast control — so a keyboard user can bypass
    the navigation without a mouse user ever seeing it.
--}}
<a href="#{{ $target }}" {{ $attributes->merge(['class' => 'skip-link']) }}>
    {{ $slot->isEmpty() ? __('common.skip_to_content') : $slot }}
</a>
