<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientArchivedResource;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\NotificationSystem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Notification;

class PatientController extends Controller
{
    public function store(StorePatientRequest $request)
    {
        $user=Auth::user()->id;
        $validated=$request->validated();
        $validated['user_id']=$user;
        $validated['is_active']=true;
        $patient=Patient::create($validated);
        $admins = User::where('type_user', 'admin')->get();
        Notification::send($admins, new NotificationSystem([
            'title' => 'New Patient Registration',
            'message' => 'Patient ' . (Auth::user()->name ?? 'Guest') . ' has successfully created a new medical profile at the center.',
            'type' => 'NEW_PATIENT_REGISTRATION',
            'url' => '',

        ]));
        return response()->json([
            'success' => true,
            'message'=>'Welcome to our center',
            'data'=>new PatientResource($patient)
            ], 201);
    }
    public function update(UpdatePatientRequest $request)
    {
        $user=Auth::user();
        $patient=$user->patient;
        if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'Patient profile not found'
        ], 404);
    }
        $validated=$request->validated();
        $patient->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Patient data updated successfully',
            'data'    => new PatientResource($patient->load('user'))
        ], 200);
    }
    public function index()
    {
        $patient = Patient::active()->with('user')->latest()->get();
        if ($patient->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'There are no currently registered patients',
            'data'    => []
        ], 200);
    }
        return response()->json([
            'success' => true,
            'message' => 'The patient list was successfully retrieved',
            'data'    => PatientResource::collection($patient)
        ], 200);
    }

public function show(int $id)
{
    $patient = Patient::with([
        'user',
        'appointments' => function($query) {
            $query->where('appointment_date', '>=', now()->toDateString())
                ->orderBy('appointment_date', 'asc');
        },
        'appointments.doctor.user',
        'appointments.doctor.department'
    ])->find($id);
    if (!$patient) {
        return response()->json([
            'success' => false,
            'message' => 'Sorry, the patient is not in our records'
        ], 404);
    }
    return response()->json([
        'success' => true,
        'message' => 'The patient is data and upcoming appointments were successfully retrieved',
        'data'    => new PatientResource($patient)
    ], 200);
}
    public function getDisabledPatient()
    {
        $inactiveUsers = Patient::inactive()->latest()->get();
        if ($inactiveUsers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'There are currently no disabled accounts',
                'data'    => []
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'The list of disabled accounts was successfully retrieved',
            'data' => PatientResource::collection($inactiveUsers)
        ], 200);
    }
    public function archive($id)
    {
        $patient = Patient::findOrFail($id);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found'
            ], 404);
        }
        $patient->delete();
        return response()->json([
            'success' => true,
            'message' => 'The patient and all associated data have been archived '
            ], 200);
    }
    public function indexArchived()
    {
        $archivedPatients=Patient::onlyTrashed() ->with([
            'user' => function ($query) {
                $query->withTrashed();
            }
        ])->get();
        if ( $archivedPatients->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'The archive currently contains no patients.',
            'data'    => []
        ], 200);
    }
        return response()->json([
            'success' => true,
            'message' => 'Patient data were successfully retrieved from the archive.',
            'data'    => PatientArchivedResource::collection( $archivedPatients)
        ], 200);
    }
    public function restore($id)
    {
        $patient = Patient::withTrashed()->findOrFail($id);
        $patient->restore();
        return response()->json([
            'success' => true,
            'message' => 'Patient restored successfully',
            'data'=>new PatientResource( $patient->load(['user']))
            ], 200);
    }
    public function destroy($id)
    {
        $patient=Patient::withTrashed()->findOrFail($id);
        $patient->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'The patient and all associated data have been deleted'
            ], 200);
    }
}
