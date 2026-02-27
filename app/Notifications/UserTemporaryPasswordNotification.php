<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserTemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $username,
        private readonly string $temporaryPassword,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Credenciales de acceso')
            ->greeting('Hola,')
            ->line('Tu cuenta ha sido creada en el sistema.')
            ->line("Usuario: {$this->username}")
            ->line("Contraseña temporal: {$this->temporaryPassword}")
            ->line('Por seguridad, cambia la contraseña después de iniciar sesión.')
            ->action('Ir al inicio de sesión', url('/login'));
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
