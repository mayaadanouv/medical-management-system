<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIsDoctorApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();

    // التأكد أن المستخدم طبيب ومسجل دخول
    if ($user && $user->type_user === 'doctor') {
        $status = $user->doctor->status;

        // 1. إذا كان مقبولاً (Approved) - يمر فوراً
        if ($status === 'approved') {
            return $next($request);
        }

        // 2. إذا كان مرفوضاً (Rejected)
        if ($status === 'rejected') {
            return response()->json([
                'message' => 'We regret to inform you that your application to join the center has been rejected.'
                ], 403);
        }

        // 3. أي حالة أخرى (بما أنها تلت قيم فقط، فالباقي هو قيد المراجعة)
        return response()->json(['message' => 'Your account is still under admin review'], 403);
    }

    // إذا لم يكن طبيباً
    return response()->json(['message' => 'You are not authorized to enter'], 403);
}
}
