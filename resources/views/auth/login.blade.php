<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Hospital Management System</title>

    {{-- Bootstrap 5 CSS (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =============================================
           Hospital Login Page — Custom Styles
           We only add styles Bootstrap doesn't cover.
           ============================================= */

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            /* Deep navy-blue gradient background for a professional medical feel */
            background: linear-gradient(135deg, #0f2557 0%, #1a3a6b 50%, #0d6e6e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* The white login card in the center */
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }

        /* Top colored banner inside the card */
        .login-header {
            background: linear-gradient(135deg, #1a3a6b, #0d6e6e);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        /* The hospital cross/plus icon in the header */
        .login-header .hospital-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }

        /* Form section below the header */
        .login-body {
            padding: 2rem;
        }

        /* Input field focus color — teal instead of Bootstrap blue */
        .form-control:focus {
            border-color: #0d6e6e;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 110, 0.2);
        }

        /* Input group icon background */
        .input-group-text {
            background-color: #f8f9fa;
            border-right: 0;
            color: #0d6e6e;
        }

        /* Make icon and input look connected */
        .input-group .form-control {
            border-left: 0;
        }

        /* Login button — teal color matching the theme */
        .btn-login {
            background: linear-gradient(135deg, #1a3a6b, #0d6e6e);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: opacity 0.2s ease;
        }

        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }

        /* Role info badges at the bottom */
        .demo-credentials {
            background: #f0f7f7;
            border: 1px solid #c3e0e0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
        }

        .demo-credentials .badge-role {
            font-size: 0.72rem;
            padding: 0.25em 0.55em;
        }
    </style>
</head>
<body>

    <div class="login-card">

        {{-- ===== TOP HEADER BANNER ===== --}}
        <div class="login-header">
            <div class="hospital-icon">
                <i class="bi bi-hospital-fill"></i>
            </div>
            <h4 class="fw-bold mb-1">Hospital Management</h4>
            <p class="mb-0 opacity-75" style="font-size: 0.9rem;">
                Secure Staff Portal — Please sign in
            </p>
        </div>

        {{-- ===== FORM BODY ===== --}}
        <div class="login-body">

            {{-- Success message (e.g. after logout) --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- General error messages --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ===== LOGIN FORM ===== --}}
            {{--
                action="{{ route('login') }}" → sends POST to /login
                The @csrf directive inserts a hidden CSRF token field.
                Laravel checks this token to prevent Cross-Site Request Forgery attacks.
            --}}
            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                {{-- Email Field --}}
                <div class="mb-3">
                    <label for="email" class="form-label fw-500 text-dark">
                        Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        {{--
                            old('email') re-fills the field if validation fails
                            so the user doesn't have to retype their email.
                            :class adds 'is-invalid' Bootstrap class when there's an email error.
                        --}}
                        <input
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            autofocus
                            required
                        >
                    </div>
                    {{-- Show email-specific error below the field --}}
                    @error('email')
                        <div class="text-danger mt-1" style="font-size: 0.85rem;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Password Field --}}
                <div class="mb-3">
                    <label for="password" class="form-label fw-500 text-dark">
                        Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                        {{-- Toggle password visibility button --}}
                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            id="togglePassword"
                            title="Show/Hide Password"
                        >
                            <i class="bi bi-eye-fill" id="eyeIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger mt-1" style="font-size: 0.85rem;">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Remember Me Checkbox --}}
                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="remember"
                            name="remember"
                        >
                        <label class="form-check-label text-secondary" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-login w-100" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In to System
                </button>
            </form>

            {{-- ===== DEMO CREDENTIALS (Development Only) ===== --}}
            <div class="demo-credentials mt-3">
                <div class="text-muted mb-1 fw-semibold" style="font-size: 0.8rem;">
                    <i class="bi bi-info-circle me-1"></i> Demo Credentials
                </div>
                <div class="d-flex flex-column gap-1">
                    <div>
                        <span class="badge bg-danger badge-role">Admin</span>
                        <span class="text-muted ms-1">admin@hospital.com</span>
                        <span class="text-muted">/</span>
                        <span class="text-muted">password123</span>
                    </div>
                    <div>
                        <span class="badge bg-success badge-role">Doctor</span>
                        <span class="text-muted ms-1">doctor@hospital.com</span>
                        <span class="text-muted">/</span>
                        <span class="text-muted">password123</span>
                    </div>
                    <div>
                        <span class="badge bg-primary badge-role">Reception</span>
                        <span class="text-muted ms-1">reception@hospital.com</span>
                        <span class="text-muted">/</span>
                        <span class="text-muted">password123</span>
                    </div>
                </div>
            </div>

        </div>{{-- end login-body --}}

        {{-- Footer --}}
        <div class="text-center text-muted pb-3" style="font-size: 0.78rem;">
            &copy; {{ date('Y') }} Hospital Management System. All rights reserved.
        </div>

    </div>{{-- end login-card --}}

    {{-- Bootstrap 5 JS (CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // =============================================
        // Toggle Password Visibility
        // When user clicks the eye icon, switch
        // password field between 'text' and 'password'
        // =============================================
        const toggleBtn  = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon    = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function () {
            // Check current type and swap it
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        });

        // =============================================
        // Show loading state on form submit
        // Prevents double-clicking the submit button
        // =============================================
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
        });
    </script>

</body>
</html>
