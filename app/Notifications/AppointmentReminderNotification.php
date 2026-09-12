<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'          => $this->details['title'],
            'message'        => $this->details['message'],
            'appointment_id' => $this->details['appointment_id'],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'          => $this->details['title'],
            'message'        => $this->details['message'],
            'appointment_id' => $this->details['appointment_id'],
        ]);
    }
}
