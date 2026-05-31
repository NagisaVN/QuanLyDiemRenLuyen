<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary px-4']) }}>
    {{ $slot }}
</button>
