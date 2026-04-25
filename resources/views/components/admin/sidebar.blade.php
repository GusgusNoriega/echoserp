@props([
    'navigation' => [],
    'brand' => [],
])

<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navegacion principal">
    <div class="sidebar-scroll">
        <div class="sidebar-header">
            <x-admin.brand-lockup
                :brand="$brand"
                :href="route('admin.dashboard')"
                variant="sidebar"
                :show-copy="false"
            />

            <button class="sidebar-close" type="button" data-sidebar-close aria-label="Cerrar menu lateral">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" />
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            @foreach ($navigation as $item)
                @if (($item['type'] ?? 'link') === 'group')
                    @php
                        $groupActive = collect($item['children'] ?? [])
                            ->contains(fn (array $child) => filled($child['route'] ?? null) && request()->routeIs($child['route']));
                    @endphp

                    <details class="nav-group" data-nav-group @if ($groupActive) open @endif>
                        <summary>
                            <span class="nav-link nav-link--summary">
                                <x-admin.nav-icon :name="$item['icon'] ?? 'layers'" />

                                <span class="nav-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['description'] ?? 'Modulo del panel.' }}</small>
                                </span>

                                <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M7 10l5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </summary>

                        <div class="nav-children">
                            @foreach (($item['children'] ?? []) as $child)
                                @php
                                    $childRoute = $child['route'] ?? null;
                                    $childActive = filled($childRoute) && request()->routeIs($childRoute);
                                @endphp

                                @if (filled($childRoute))
                                    <a class="nav-sublink @if ($childActive) is-active @endif" href="{{ route($childRoute) }}">
                                        <span class="nav-sublink-bullet" aria-hidden="true"></span>

                                        <span class="nav-copy">
                                            <strong>{{ $child['label'] }}</strong>
                                            <small>{{ $child['description'] ?? 'Disponible para ampliar.' }}</small>
                                        </span>

                                        @if (! empty($child['badge']))
                                            <span class="nav-badge">{{ $child['badge'] }}</span>
                                        @endif
                                    </a>
                                @else
                                    <button class="nav-sublink nav-sublink--mock" type="button" aria-disabled="true">
                                        <span class="nav-sublink-bullet" aria-hidden="true"></span>

                                        <span class="nav-copy">
                                            <strong>{{ $child['label'] }}</strong>
                                            <small>{{ $child['description'] ?? 'Disponible para ampliar.' }}</small>
                                        </span>

                                        @if (! empty($child['badge']))
                                            <span class="nav-badge">{{ $child['badge'] }}</span>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </details>
                @else
                    @php
                        $itemActive = request()->routeIs($item['route']);
                    @endphp

                    <a class="nav-link @if ($itemActive) is-active @endif" href="{{ route($item['route']) }}">
                        <x-admin.nav-icon :name="$item['icon'] ?? 'home'" />

                        <span class="nav-copy">
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['description'] ?? 'Vista principal.' }}</small>
                        </span>

                        @if (! empty($item['badge']))
                            <span class="nav-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
</aside>
