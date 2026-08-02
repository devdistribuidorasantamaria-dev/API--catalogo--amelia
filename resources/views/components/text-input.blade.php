@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'a-input']) }}>
