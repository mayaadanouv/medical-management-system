<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOwnerOrStaff
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

        if ($user->type_user === 'admin') {
            return $next($request);
        }

        $routeId = $request->route('id');
        if ($user->type_user === 'doctor') {
            if ($user->doctor) {
                $doctorId = $user->doctor->id;

                $hasRelationship = Appointment::where('patient_id', $routeId)
                                            ->where('doctor_id', $doctorId)
                                            ->exists();
                if ($hasRelationship) {
                    return $next($request);
                }
            }
        }

        if ($routeId) {
            $isOwner = Patient::where('id', $routeId)
                        ->where('user_id', $user->id)
                        ->exists();
            if ($isOwner) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Sorry, this data is restricted to owners, doctors, and administration only'
        ], 403);
    }
}
