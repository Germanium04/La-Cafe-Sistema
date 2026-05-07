<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleAuth
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (session('user_role') !== $role) {
            return redirect('/')->with('error', 'Access denied.');
        }

        return $next($request);
    }
}