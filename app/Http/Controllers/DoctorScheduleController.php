<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\DoctorScheduleResource;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Schedule;

class DoctorScheduleController extends Controller
{
    public function index()
{
    $doctors = Doctor::approved()->with(['user', 'schedules','department'])->get();
    $doctors->each(function ($doctor) {
        $doctor->setRelation('schedules', $doctor->schedules->filter(function ($schedule) {
            return is_null($schedule->pivot->deleted_at);
        })->values());
    });
    return response()->json([
        'success' => true,
        'message' => 'All doctors and their working hours have been recorded.',
        'data'    => DoctorResource::collection($doctors)
    ], 200);
}
    public function store (StoreDoctorScheduleRequest $request)
    {
        $doctor =Auth::user()->doctor;
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found'
                ], 404);
        }
        $validate = $request->validated();
        $doctor->schedules()->syncWithoutDetaching([
            $validate['schedule_id'] => [
                'start_time' => $validate['start_time'],
                'end_time' => $validate['end_time']
            ]
        ]);
        $doctor->load('schedules');
        return response()->json([
            'success' => true,
            'message' => 'Schedule added successfully',
            'data'=> new DoctorScheduleResource($doctor)
            ], 200);
    }
    public function update(UpdateDoctorScheduleRequest $request,$ScheduleId)
    {
        $doctor=Auth::user()->doctor;
        $validate = $request->validated();
        $doctor->schedules()->updateExistingPivot($ScheduleId,
        [
            'start_time' => $validate['start_time'],
            'end_time' => $validate['end_time']
        ]);
        $doctor->load('schedules');
        return response()->json([
            'success' => true,
            'message' => 'the update was successfully',
            'data'=> new DoctorScheduleResource($doctor)
            ], 200);
    }
    public function destroy($ScheduleId)
    {
        $doctor=Auth::user()->doctor;
        if (!$doctor)
            {
        return response()->json([
            'success' => false,
            'message' => 'Doctor profile not found'
            ], 404);
            }
        $pivotRecord =DoctorSchedule::where('doctor_id', $doctor->id)
                    ->where('schedule_id', $ScheduleId)
                    ->first();
    if (!$pivotRecord) {
        return response()->json([
            'success' => false,
            'message' => 'This day is not on the schedule'
            ],404);
            }
        $pivotRecord->delete();
            return response()->json([
            'success' => true,
            'message' => 'The removal process was successful.'
    ], 200);
    }
    public function show($doctorId)
{
    $doctor = Doctor::with(['user', 'department', 'schedules'])->findOrFail($doctorId);
    $doctorName=$doctor->user->name;
    return response()->json([
        'success' => true,
        'message'=>" Schedules for Dr. $doctorName retrieved successfully ",
        'data'=> new DoctorScheduleResource($doctor)
    ], 200);
}
}
