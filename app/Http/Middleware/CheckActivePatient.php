<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActivePatient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    // 1. السماح للأدمن والدكتور مباشرة
    if (in_array($user->type_user, ['admin', 'doctor'])) {
        return $next($request);
    }

    // 2. التحقق من المريض بشكل آمن
    if ($user->type_user === 'patient') {
        // نتحقق أولاً هل للمستخدم سجل مريض؟ وهل هذا السجل نشط؟
        if ($user->patient && $user->patient->is_active) {
            return $next($request);
        }
    }

    return response()->json(['message' => 'Account disabled or not found'], 403);
}
}
