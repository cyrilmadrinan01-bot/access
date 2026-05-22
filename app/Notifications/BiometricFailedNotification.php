<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BiometricFailedNotification extends Notification
{
    public $log;
    public $exception;

    public function __construct($log, $exception)
    {
        $this->log = $log;
        $this->exception = $exception;
    }

    public function via($notifiable)
    {
        return ['mail', 'slack'];   // Enables BOTH email & Slack
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("⚠ Biometric Processing Failed")
            ->line("Employee: {$this->log->empnum}")
            ->line("Date: {$this->log->dated}")
            ->line("TimeLog: {$this->log->timeLog}")
            ->line("Retries: {$this->log->retry_count}")
            ->line("Error: {$this->exception->getMessage()}")
            ->line("This log will stop retrying.");
    }

    public function toSlack($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\SlackMessage)
            ->error()
            ->content("Biometric processing FAILED. *{$this->log->empnum}*")
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'TimeLog' => $this->log->timeLog,
                    'Retries' => $this->log->retry_count,
                    'Error'   => $this->exception->getMessage(),
                ]);
            });
    }
}
