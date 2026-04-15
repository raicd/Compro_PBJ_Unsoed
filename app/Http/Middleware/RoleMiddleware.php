<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = strtolower(trim((string)($user->role ?? '')));
        $expected = strtolower(trim($role));

        if ($userRole !== $expected) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}