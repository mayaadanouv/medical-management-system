<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use App\Notifications\NotificationSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Notification;

class DoctorController extends Controller
{
    public function store(StoreDoctorRequest $request)
    {
        $user=Auth::user()->id;
        $validated=$request->validated();
        $validated['user_id']=$user;
        $validated['status'] = 'pending';
        if($request->hasFile('profile_image'))
            {
                $path=$request->file('profile_image')->store('doctors/profiles','public');
                $validated['profile_image']=$path;
            }
        if ($request->hasFile('certificate_image'))
            {
                $path=$request->file('certificate_image')->store('doctors/certificates', 'public');
                $validated['certificate_image'] =$path;
            }
        $doctor=Doctor::create($validated);
        $admins = User::where('type_user', 'admin')->get();
        Notification::send($admins, new NotificationSystem([
            'title' => 'New Doctor Registration Request',
            'message' => 'A new registration request has been submitted by Dr. ' . (Auth::user()->name ?? 'New Doctor') . '. Approval is pending.',
            'type' => 'NEW_DOCTOR_REGISTRATION',
            'url' => '',

        ]));
        $doctor->load(['user', 'department']);
        return response()->json([
            'success' => true,
            'message' => 'Your request has been submitted successfully,Please wait for administrative approval',
            'data'=>new DoctorResource($doctor)
            ], 201);
    }
   // عرض الاطباء المقبولين فقط
    public function index()
    {
        $doctors = Doctor::approved()->with(['user', 'department'])->get();
        return response()->json([
        'success' => true,
        'message' => 'The list of doctors was successfully retrieved',
        'data'    => DoctorResource::collection($doctors)
    ], 200);
    }
   // عرض الاطباء في قسم محدد
    public function getDoctorsByDepartment(int $department_id)
    {
        $doctors = Doctor::approved()->where('department_id', $department_id)->with(['user', 'department'])->get();
        if ($doctors->isEmpty())
        {
        return response()->json([
            'success' => false,
            'message' => 'There are no doctors in this department at the moment.'
        ], 404);
        }
        return response()->json( [
            'success' => true,
            'message' => 'The list of department doctors was successfully retrieved',
            'data'    => DoctorResource::collection($doctors)
        ], 200);
    }
    public function show(int $id)
    {
        $doctor=Doctor::with(['user', 'department', 'schedules'])->find($id);
    if (!$doctor)
        {
        return response()->json([
            'success' => false,
            'message' => 'Doctor not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message'=>'The doctor is data was successfully retrieved',
            'data'    => new DoctorResource($doctor)
        ], 200);
    }
    public function update(UpdateDoctorRequest $request)
    {
        $user=Auth::user();
        $doctor=$user->doctor;
        if (!$doctor) {
        return response()->json([
            'success' => false,
            'message' => 'Doctor profile not found'
            ], 404);
    }
        $validated=$request->validated();
        if($request->hasFile('profile_image'))
            {
                if($doctor->profile_image){ Storage::disk('public')->delete($doctor->profile_image);}
                $path=$request->file('profile_image')->store('doctors/profiles','public');
                $validated['profile_image']=$path;
            }
        $doctor->update($validated);
        return response()->json([
            'success' => true,
            'message'=>' Your data has been successfully updated',
            'data'    => new DoctorResource($doctor->load(['user', 'department']))
        ], 200);
    }
    //عرض الاطباء يلي بحالة الانتظار
    public function getPendingDoctors()
    {
        $pendingDoctors = Doctor::pending()->get();
        if ($pendingDoctors->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'There are currently no pending doctor requests',
            'data'    => []
        ], 200);
    }
        return response()->json([
            'success' => true,
            'message'=>' The list of pending doctor requests has been retrieved',
            'data'    => DoctorResource::collection($pendingDoctors)
        ], 200);
    }
    //عرض الاطباء يلي بحالة رفض
    public function getrejectedDoctor()
    {
        $rejectedDoctor=Doctor::Rejected()->get();
        if ($rejectedDoctor->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'There are currently no rejected doctors',
            'data'    => []
        ], 200);
    }
        return response()->json([
            'success' => true,
            'message' => 'The list of rejected doctors was successfully retrieved',
            'data'    => DoctorResource::collection($rejectedDoctor)
        ], 200);
    }
    //ارشفت الطبيب وكل البيانات التعلقة فيه
    public function archive($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();
        return response()->json([
            'success' => true,
            'message' => 'The doctor and all his data were successfully transferred to the archive'
            ], 200);
    }
    // جلب الطبيب من الأرشيف واستعادته مع توابعه
    public function restore($id)
    {
        $doctor = Doctor::withTrashed()->findOrFail($id);
        $doctor->restore();
        return response()->json([
            'success' => true,
            'message' => 'The doctor and all his powers were successfully restored',
            'data'    => new DoctorResource($doctor->load(['user', 'department']))
            ]);
    }
     // عرض الأطباء المؤرشفين مع بيانات حساباتهم
    public function indexArchived()
    {
        $archived = Doctor::onlyTrashed()->with(['user', 'schedules'])->get();
        if ($archived->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'The archive is currently empty',
            'data'    => []
        ], 200);
    }
        return response()->json([
        'success' => true,
        'message' => 'The archived doctors were successfully brought in',
        'data'    => DoctorResource::collection($archived)
    ], 200);
    }
      //تابع الحذف النهائي
    public function destroy($id)
    {
        $doctor = Doctor:: withTrashed()->findOrFail($id);
        $doctor->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'The account and all associated data have been deleted'
            ],200);
    }


}
