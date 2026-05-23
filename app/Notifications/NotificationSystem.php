<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\Channel;

class NotificationSystem extends Notification implements ShouldQueue
{
    use Queueable;
    public $details;

    /*
     * Create a new notification instance.
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['broadcast', 'database'];

        if ($notifiable->type_user == 'doctor' || $notifiable->type_user == 'admin') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->details['title'])
            ->greeting('Hello ' . ($notifiable->name ?? 'User'))
            ->line($this->details['message']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->details['title'],
            'message' => $this->details['message'],
            'type' => $this->details['type'],
            'url' => $this->details['url'] ?? null,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->details['title'],
            'message' => $this->details['message'],
        ]);
    }

    /**
     * تحديد اسم الحدث المخصص ليفهمه Pusher بوضوح
     */
    public function broadcastType(): string
    {
        return 'NewNotification';
    }

    /**
     * البث عبر قناة عامة لكي يظهر مباشرة في الـ Debug Console دون قيود فرونت إند
     */
    public function broadcastOn(): array
    {
        return [new Channel('medical-channel')];
    }
}
