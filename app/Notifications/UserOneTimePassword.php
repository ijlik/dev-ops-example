<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class UserOneTimePassword extends Notification
{
    use Queueable;

    private string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function via(): array
    {
        return [
            'mail'
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->otp . ' adalah kode verifikasi anda.')
            ->greeting('Kode verifikasi anda adalah')
            ->line(new HtmlString('<h1 style="text-align: center; color: #262626; font-size: 40px; border: 2px solid #cfcfcf; border-radius: 25px; padding: 10px;">' . $this->otp . '</h1>'))
            ->line('Kode verifikasi kedaluwarsa setelah 30 menit');
    }
}
