<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Cafe Sistema – @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
</head>
<body>

<nav>
    <a href="/" class="nav-brand">
        <div class="nav-logo">☕</div>
        <div class="nav-brand-text">
            <span>La Cafe</span>
            <span>Sistema</span>
        </div>
    </a>

    <div class="nav-links">
        <a href="/"          class="{{ request()->is('/')          ? 'active' : '' }}">Dashboard</a>
        <a href="/orders"    class="{{ request()->is('orders*')    ? 'active' : '' }}">Orders</a>
        <a href="/products"  class="{{ request()->is('products*')  ? 'active' : '' }}">Products</a>
        <a href="/inventory" class="{{ request()->is('inventory*') ? 'active' : '' }}">Inventory</a>

        {{-- Reports: admin only --}}
        @if(session('user_role') === 'admin')
            <a href="/reports" class="{{ request()->is('reports*') ? 'active' : '' }}">Reports</a>
        @endif
    </div>

    <div class="nav-right">
        @php
            $userName    = session('user_name', 'User');
            $navParts    = explode(' ', $userName);
            $navInitials = strtoupper(substr($navParts[0], 0, 1) . substr($navParts[1] ?? '', 0, 1));
        @endphp

        {{-- Role badge --}}
        @if(session('user_role') === 'admin')
            <span class="nav-role-badge nav-role-admin">Admin</span>
        @else
            <span class="nav-role-badge nav-role-staff">Staff</span>
        @endif

        <div class="nav-avatar">{{ $navInitials }}</div>
        <span class="nav-staff-name">{{ $userName }}</span>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="nav-signout" style="background-color: transparent;">Sign out</button>
        </form>
    </div>
</nav>

@yield('content')

</body>
</html>

<style>
    .nav-role-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .nav-role-admin { background: #fef9c3; color: #854d0e; }
    .nav-role-staff { background: #dcfce7; color: #166534; }
    .stat-value {text-align: right;}
</style>