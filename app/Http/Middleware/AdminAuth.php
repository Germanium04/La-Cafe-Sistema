<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_role') !== 'admin') {
            return redirect('/')->with('error', 'Access denied. Admins only.');
        }

        return $next($request);
    }
}