@props([
    'id',
    'title',
    'description' => null,
    'size' => 'default',
    'kicker' => 'Formulario',
])

<div class="modal-shell" id="{{ $id }}" data-modal hidden aria-hidden="true">
    <button class="modal-shell__backdrop" type="button" data-modal-close aria-label="Cerrar ventana"></button>

    <section
        class="modal-shell__dialog @if ($size === 'wide') modal-shell__dialog--wide @endif"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
    >
        <header class="modal-shell__header">
            <div>
                <p class="section-kicker">{{ $kicker }}</p>
                <h2 id="{{ $id }}-title">{{ $title }}</h2>

                @if (filled($description))
                    <p class="modal-shell__description">{{ $description }}</p>
                @endif
            </div>

            <button class="modal-shell__close" type="button" data-modal-close aria-label="Cerrar ventana">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                </svg>
            </button>
        </header>

        <div class="modal-shell__content">
            {{ $slot }}
        </div>
    </section>
</div>
