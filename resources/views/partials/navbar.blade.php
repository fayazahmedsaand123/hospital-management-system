@php
    $user = auth()->user();
    $initials = \Illuminate\Support\Str::of($user->name)
        ->explode(' ')
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<header class="topbar">
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
    </button>

    <div>
        <h1 class="page-heading">@yield('page-title', 'Dashboard')</h1>
        <p class="page-sub">@yield('page-subtitle', 'Hospital Management System')</p>
    </div>

    <div class="topbar-right">
        <span class="topbar-date">
            <i class="bi bi-calendar3"></i>
            {{ now()->format('D, d M Y') }}
        </span>

        <div class="dropdown">
            <button class="btn btn-link p-0 border-0 d-flex align-items-center gap-2 text-decoration-none"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper($initials) }}</span>
                <span class="user-meta d-none d-sm-block text-start">
                    <span class="user-name d-block">{{ $user->name }}</span>
                    <span class="user-role">{{ $user->role }}</span>
                </span>
                <i class="bi bi-chevron-down text-muted small"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li class="px-3 py-2">
                    <div class="fw-semibold">{{ $user->name }}</div>
                    <div class="text-muted small">{{ $user->email }}</div>
                    <span class="badge badge-soft-primary mt-1 text-capitalize">{{ $user->role }}</span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
