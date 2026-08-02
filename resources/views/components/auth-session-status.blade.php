@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-[11px] uppercase tracking-label text-ink']) }}>
        {{ $status }}
    </div>
@endif
