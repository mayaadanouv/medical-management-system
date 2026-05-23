<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * تحديث إعدادات النظام (السعر وزمن المعاينة)
     */
    public function updateSettings(Request $request)
    {
        $validatedData = $request->validate([
            'appointment_price' => 'sometimes|numeric|min:0',
            'duration'          => 'sometimes|integer|min:1',
        ]);
        $settings = Setting::updateOrCreate(
            ['id' => 1],
            $validatedData
        );

        return response()->json([
            'success' => true,
            'message' => 'The update was successful',
        ]);
    }
}

