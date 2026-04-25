@props([
    'brand' => [],
    'href' => null,
    'variant' => 'sidebar',
    'showCopy' => true,
])

@php
    $brandName = $brand['name'] ?? config('app.name', 'Laravel');
    $brandSubtitle = $brand['subtitle'] ?? 'Panel base';
    $brandLogo = $brand['logo'] ?? null;
    $brandLogoAlt = $brand['logo_alt'] ?? $brandName;
    $wrapperClasses = 'brand-lockup brand-lockup--'.$variant;
@endphp

@if (filled($href))
    <a class="{{ $wrapperClasses }}" href="{{ $href }}">
        @if (filled($brandLogo))
            <span class="brand-logo brand-logo--{{ $variant }}">
                <img src="{{ asset($brandLogo) }}" alt="{{ $brandLogoAlt }}">
            </span>
        @else
            <span class="brand-mark">{{ strtoupper(substr($brandName, 0, 1)) }}</span>
        @endif

        @if ($showCopy)
            <span class="brand-copy brand-copy--{{ $variant }}">
                <strong>{{ $brandName }}</strong>
                <small>{{ $brandSubtitle }}</small>
            </span>
        @endif
    </a>
@else
    <div class="{{ $wrapperClasses }}">
        @if (filled($brandLogo))
            <span class="brand-logo brand-logo--{{ $variant }}">
                <img src="{{ asset($brandLogo) }}" alt="{{ $brandLogoAlt }}">
            </span>
        @else
            <span class="brand-mark">{{ strtoupper(substr($brandName, 0, 1)) }}</span>
        @endif

        @if ($showCopy)
            <span class="brand-copy brand-copy--{{ $variant }}">
                <strong>{{ $brandName }}</strong>
                <small>{{ $brandSubtitle }}</small>
            </span>
        @endif
    </div>
@endif
