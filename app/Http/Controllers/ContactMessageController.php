<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'required_without:email|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'message' => 'required|string|max:3000',
        ]);

        ContactMessage::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully,We will contact you via WhatsApp soon.'
        ], 201);
    }

    public function adminIndex()
    {
        $messages = ContactMessage::with('department')->latest()->get();

        return response()->json([
            'success' => true,
            'data' =>  ContactMessageResource::collection( $messages)
        ], 200);
    }
}
