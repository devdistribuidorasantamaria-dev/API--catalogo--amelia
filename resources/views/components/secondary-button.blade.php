<button {{ $attributes->merge(['type' => 'button', 'class' => 'a-btn a-btn-ghost']) }}>
    {{ $slot }}
</button>
