@php($user = auth()->user())
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-hospital-fill"></i>
        </div>
        <div>
            <p class="brand-title">Hospital Management</p>
            <span class="brand-sub">Staff Portal</span>
        </div>
    </div>

    <div class="sidebar-scroll">
        <nav class="nav flex-column">
            <div class="nav-section">Overview</div>
            <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            @if ($user->isAdminOrReceptionist())
                <div class="nav-section">Administration</div>
                <a class="nav-link @if (request()->routeIs('departments.*')) active @endif" href="{{ route('departments.index') }}">
                    <i class="bi bi-diagram-3-fill"></i> Departments
                </a>
                <a class="nav-link @if (request()->routeIs('doctors.*')) active @endif" href="{{ route('doctors.index') }}">
                    <i class="bi bi-person-badge-fill"></i> Doctors
                </a>
            @endif

            @if ($user->isAdminOrReceptionist())
                <div class="nav-section">Front Desk</div>
                <a class="nav-link @if (request()->routeIs('patients.*')) active @endif" href="{{ route('patients.index') }}">
                    <i class="bi bi-people-fill"></i> Patients
                </a>
            @endif

            <a class="nav-link @if (request()->routeIs('appointments.*')) active @endif" href="{{ route('appointments.index') }}">
                <i class="bi bi-calendar2-week-fill"></i> Appointments
            </a>

            @if ($user->isAdmin() || $user->isDoctor())
                <div class="nav-section">Clinical</div>
                <a class="nav-link @if (request()->routeIs('prescriptions.*')) active @endif" href="{{ route('prescriptions.index') }}">
                    <i class="bi bi-clipboard2-pulse-fill"></i> Prescriptions
                </a>
            @endif

            @if ($user->isAdminOrReceptionist())
                <div class="nav-section">Finance</div>
                <a class="nav-link @if (request()->routeIs('bills.*')) active @endif" href="{{ route('bills.index') }}">
                    <i class="bi bi-receipt-cutoff"></i> Billing &amp; Payments
                </a>
            @endif

            <div class="nav-section">Analytics</div>
            <a class="nav-link @if (request()->routeIs('reports.*')) active @endif" href="{{ route('reports.index') }}">
                <i class="bi bi-bar-chart-line-fill"></i> Reports
            </a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-shield-check text-info"></i>
            <span class="fw-semibold text-white">Secure Session</span>
        </div>
        Signed in as <span class="text-white text-capitalize">{{ $user->role }}</span>
    </div>
</aside>
