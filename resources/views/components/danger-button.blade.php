<button {{ $attributes->merge(['type' => 'submit', 'class' => 'a-btn a-btn-danger']) }}>
    {{ $slot }}
</button>
