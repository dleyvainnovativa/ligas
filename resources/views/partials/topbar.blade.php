<header class="app-topbar">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button type="button" class="menu-toggle" data-action="open-sidebar" aria-label="Abrir menú">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h5 class="mb-0">@yield('page-title', 'Padel Leagues')</h5>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button class="theme-toggle" data-theme-toggle aria-label="Alternar tema">
            <i class="fa-solid fa-sun sun"></i>
            <i class="fa-solid fa-moon moon"></i>
        </button>

        @auth
        @php $u = auth()->user(); @endphp
        <div class="dropdown">
            <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="user-avatar">
                    {{ strtoupper(mb_substr($u->name ?: $u->email, 0, 1)) }}
                </span>
                <span class="user-menu-email d-none d-sm-inline">{{ $u->email }}</span>
                <i class="fa-solid fa-chevron-down text-muted" style="font-size:10px;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fa-solid fa-right-from-bracket me-2 text-muted"></i> Cerrar sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        @endauth
    </div>
</header>