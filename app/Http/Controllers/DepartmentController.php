<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepartmentController extends Controller
{
    public function index()
    {
        $department=Department::all();
        return response()->json([
        'success' => true,
        'message' => 'All departments retrieved successfully',
        'data'    => DepartmentResource::collection($department)
    ], 200);
    }
    public function show($id)
    {
        $department=Department::find($id);
        $departmentNeme=$department->specialty_name;
            if (!$department) {
        return response()->json([
            'success' => false,
            'message' => 'Department not found'
        ], 404);
        }
        return response()->json([
        'success' => true,
        'message' => "Department ({$departmentNeme}) details retrieved successfully",
        'data'    => new DepartmentResource($department)
    ], 200);
    }
    public function store(StoreDepartmentRequest $request)
    {
        $validated=$request->validated();
        if($request->hasFile('dept_image'))
            {
                $path=$request->file('dept_image')->store('department/image','public');
                $validated['dept_image']=$path;
            }
        $department=Department::create($validated);
        $departmentNeme=$department->specialty_name;
        return response()->json([
            'success' => true,
            'message' => "Department $departmentNeme created successfully",
            'data'    => new DepartmentResource($department)
        ], 201);
    }
    public function update(UpdateDepartmentRequest $request , $id)
    {
        $department=Department::find($id);
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
                ], 404);
        }
        $validated=$request->validated();
        if($request->hasFile('dept_image'))
            {
                if($department->dept_image){ Storage::disk('public')->delete($department->dept_image);}
                $path=$request->file('dept_image')->store('department/image','public');
                $validated['dept_image']=$path;
            }
        $department->update($validated);
        $departmentName=$department->specialty_name;
        return response()->json([
            'success' => true,
            'message'=>"Department($departmentName)updated successfully",
            'data'    => new DepartmentResource($department)
        ], 200);
    }
    //تابع لعرض الاقسام يلي تم ارشفتها
    public function archived()
    {
        $archivedDepartments=Department::onlyTrashed()->get();
        return response()->json([
            'success' => true,
            'message' => 'Archived departments retrieved successfully',
            'data'    => DepartmentResource::collection($archivedDepartments)
            ], 200);
    }
    //تابع استعادة الاقسام التي تم ارشفتها
    public function restore($id)
    {
        $department = Department::withTrashed()->findOrFail($id);
        $department->restore();
        return response()->json([
            'success' => true,
            'message' => "Department ({$department->specialty_name}) restored successfully",
            'data'    => new DepartmentResource($department)
        ],200);
    }
    //تابع الارشفة
        public function archive($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
                ], 404);
        }
        $departmentName = $department->specialty_name;
        $department->delete();
        return response()->json([
            'success' => true,
            'message' => "The department ($departmentName) and all associated data have been archived successfully",
            'data'    => [
            'archived_id' => $id
            ]], 200);
    }
    //تابع الحذف النهائي
    public function destroy($id)
    {
    try {
        $department = Department::withTrashed()->findOrFail($id);
        $departmentName = $department->specialty_name;
        $department->forceDelete();
        return response()->json([
                'success' => true,
                'message' => "The department ($departmentName) has been permanently deleted.",
                'data'    => [
                    'deleted_id' => $id
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
                'success' => false,
                'message' => 'Department not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department: ' . $e->getMessage()
            ], 422);
        }
    }
}
