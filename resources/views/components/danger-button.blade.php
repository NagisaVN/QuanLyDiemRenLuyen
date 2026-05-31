<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger px-4']) }}>
    {{ $slot }}
</button>
