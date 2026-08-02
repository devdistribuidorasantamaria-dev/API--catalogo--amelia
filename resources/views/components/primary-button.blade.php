<button {{ $attributes->merge(['type' => 'submit', 'class' => 'a-btn a-btn-solid']) }}>
    {{ $slot }}
</button>
