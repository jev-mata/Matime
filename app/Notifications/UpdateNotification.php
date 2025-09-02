<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushMessage;
class UpdateNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    private Carbon $maintenanceAt;

    public function __construct(Carbon $maintenanceAt)
    {
        $this->maintenanceAt = $maintenanceAt;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Scheduled Maintenance Notification')
            ->line('Please be advised that the system will undergo maintenance.')
            ->line('📅 Scheduled Time: ' . $this->maintenanceAt->timezone(config('app.timezone'))->format('M d, Y h:i A'))
            ->line('During this time the system may be unavailable.')
            ->line('Thank you for your understanding.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'System maintenance has been scheduled.',
            'scheduled_at' => $this->maintenanceAt->format('Y-m-d H:i:s'),
        ];
    }

    public function toBroadcast($notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'message' => 'System maintenance has been scheduled.',
            'scheduled_at' => $this->maintenanceAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Scheduled Maintenance')
            ->icon('/panso.png')
            ->body('System will be maintenance on ' . $this->maintenanceAt->format('M d, Y h:i A').'\n. This may temporarily disrupt your use of Panso, but it will be back shortly. ')
            ->action('View', url('/'));
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
