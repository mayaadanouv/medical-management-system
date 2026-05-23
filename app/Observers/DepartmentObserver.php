<?php
namespace App\Observers;

use App\Models\Department;
use Illuminate\Support\Facades\Storage;
use Exception;

class DepartmentObserver
{
    public function deleting(Department $department)
    {
        if ($department->isForceDeleting()) {
            // 1. منع الحذف النهائي لوجود أطباء
            if ($department->doctors()->withTrashed()->exists()) {
                throw new Exception("لا يمكن حذف القسم نهائياً لوجود أطباء مرتبطين به.");
            }

            // 2. حذف صورة القسم من السيرفر إذا وجدت
            if ($department->dept_image) {
                Storage::disk('public')->delete($department->dept_image);
            }
        }
    }

    public function deleted(Department $department)
    {
        if (!$department->isForceDeleting()) {
            foreach ($department->doctors as $doctor) {
                $doctor->delete();
            }
        }
    }

    public function restored(Department $department)
    {
        $department->doctors()->withTrashed()->restore();
    }
}
