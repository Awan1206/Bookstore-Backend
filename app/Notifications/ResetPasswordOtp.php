<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtp extends Notification
{
    use Queueable;

    public function __construct(protected string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Reset Password - Bookstore')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami menerima permintaan untuk mereset password akun Anda.')
            ->line('Berikut kode verifikasi Anda:')
            ->line(new \Illuminate\Support\HtmlString(
                '<div style="text-align:center; font-size:28px; font-weight:bold; letter-spacing:4px; margin:16px 0;">'.$this->code.'</div>'
            ))
            ->line('Kode ini berlaku selama 15 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }
}