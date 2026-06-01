<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sender_name'   => $this->name,
            'email'         => $this->whenNotNull($this->email),
            'phone'         => $this->phone,
            'message'       => $this->message,
            'department'    => $this->whenNotNull(
                $this->relationLoaded('department') && $this->department ? $this->department->specialty_name : null
            ),
            'sent_at'       => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
