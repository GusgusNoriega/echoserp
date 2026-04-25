@props(['name' => 'home'])

<span class="nav-icon" aria-hidden="true">
    @switch($name)
        @case('shield')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 3l7 3v5c0 4.5-2.6 8.6-7 10-4.4-1.4-7-5.5-7-10V6l7-3Z" stroke-linejoin="round" />
                <path d="M9.5 12l1.8 1.8L15 10.1" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            @break

        @case('layers')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 4 4 8l8 4 8-4-8-4Z" stroke-linejoin="round" />
                <path d="m4 12 8 4 8-4" stroke-linecap="round" stroke-linejoin="round" />
                <path d="m4 16 8 4 8-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            @break

        @case('settings')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" />
                <path d="m19.4 15 .7 1.3-1.6 2.7-1.5-.1a7.6 7.6 0 0 1-1.4.8l-.7 1.4H9.1l-.7-1.4a7.6 7.6 0 0 1-1.4-.8l-1.5.1-1.6-2.7.7-1.3a8.4 8.4 0 0 1 0-1.9l-.7-1.3 1.6-2.7 1.5.1c.4-.3.9-.6 1.4-.8l.7-1.4h5.8l.7 1.4c.5.2 1 .5 1.4.8l1.5-.1 1.6 2.7-.7 1.3c.1.6.1 1.3 0 1.9Z" stroke-linejoin="round" />
            </svg>
            @break

        @default
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1v-9.5Z" stroke-linejoin="round" />
            </svg>
    @endswitch
</span>
