@props(['value'])

<label {{ $attributes->merge(['class' => 'a-label']) }}>
    {{ $value ?? $slot }}
</label>
