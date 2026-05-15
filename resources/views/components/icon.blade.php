@props(['name'])

<svg {{ $attributes->merge(['class' => 'kb-icon']) }}>
    <use xlink:href="#icon-{{ $name }}"></use>
</svg>