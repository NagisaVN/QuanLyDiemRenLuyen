<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-success px-4']) }}>
    {{ $slot }}
</button>
