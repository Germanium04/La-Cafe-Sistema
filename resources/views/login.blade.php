<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Cafe Sistema – Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        /* ── LOGIN PAGE ONLY ── */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--bg);
        }

        /* subtle diagonal line texture over the bg */
        .login-wrap::before {
            content: '';
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
                -55deg,
                transparent,
                transparent 40px,
                rgba(200,119,58,0.04) 40px,
                rgba(200,119,58,0.04) 41px
            );
            pointer-events: none;
        }

        .login-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(44,26,14,0.13);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 400px;
            padding: 0;
            overflow: hidden;
            animation: loginFadeUp 0.35s ease both;
        }

        /* top bar — same dark brown as the navbar */
        .login-topbar {
            background: var(--nav);
            padding: 1.75rem 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .login-logo-circle {
            width: 52px;
            height: 52px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 4px;
            box-shadow: 0 4px 12px rgba(200,119,58,0.35);
        }

        .login-brand {
            text-align: center;
            line-height: 1;
        }
        .login-brand .name {
            display: block;
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .login-brand .sub {
            display: block;
            color: var(--nav-text);
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .login-shop {
            font-size: 12px;
            color: rgba(212,184,150,0.6);
            letter-spacing: 0.06em;
            margin-top: 2px;
        }

        /* form area */
        .login-body {
            padding: 1.75rem 2rem 2rem;
        }

        .login-heading {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.25rem;
            text-align: center;
        }

        /* reuse field-group / field-label / field-select from app.css */
        .field-group { margin-bottom: 14px; }
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 6px;
        }
        .field-input {
            width: 100%;
            padding: 10px 13px;
            font-size: 13.5px;
            font-family: 'Segoe UI', system-ui, sans-serif;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,119,58,0.12);
        }

        .login-btn {
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            letter-spacing: 0.02em;
            transition: background 0.15s, transform 0.1s;
        }
        .login-btn:hover  { background: var(--accent2); }
        .login-btn:active { transform: scale(0.99); }

        /* divider line between topbar and form */
        .login-divider {
            height: 1px;
            background: var(--border);
            margin: 0 2rem 1.5rem;
        }

        /* error flash — reuses .flash-error from app.css */
        .flash {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 500;
        }
        .flash-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #ef9a9a;
        }

        .login-footer {
            text-align: center;
            font-size: 11px;
            color: var(--muted);
            margin-top: 1.25rem;
            letter-spacing: 0.03em;
        }

        @keyframes loginFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-topbar { padding: 1.5rem 1.25rem 1.25rem; }
            .login-body   { padding: 1.5rem 1.25rem 1.5rem; }
            .login-divider { margin: 0 1.25rem 1.25rem; }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        {{-- TOP BAR --}}
        <div class="login-topbar">
            <div class="login-logo-circle">☕</div>
            <div class="login-brand">
                <span class="name">La Cafe Sistema</span>
                <span class="sub">Staff Portal</span>
            </div>
            <div class="login-shop">Don Macchiato Coffee Shop</div>
        </div>

        {{-- FORM BODY --}}
        <div class="login-body">
            <div class="login-heading">Sign in to your account</div>

            {{-- Flash error (e.g. wrong password) --}}
            @if(session('error'))
                <div class="flash flash-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="field-group">
                    <label class="field-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="field-input"
                        placeholder="Enter your username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="field-group" style="margin-bottom: 20px;">
                    <label class="field-label" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field-input"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="login-btn">Sign in →</button>
            </form>

            <div class="login-footer">Don Macchiato &mdash; Internal POS System</div>
        </div>

    </div>
</div>
</body>
</html>