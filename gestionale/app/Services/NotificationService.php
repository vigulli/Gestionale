<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class NotificationService
{
    private function tenantConfig(string $key): mixed
    {
        return tenant($key);
    }

    public function send(
        string $channel,
        Customer $customer,
        string $message,
        Model $notifiable,
    ): void {
        match ($channel) {
            'sms'       => $this->sendSms($customer->phone, $message, $notifiable),
            'whatsapp'  => $this->sendWhatsapp($customer->phone, $message, $notifiable),
            'email'     => $this->sendEmail($customer->email, $message, $notifiable, $customer->full_name),
            default     => null,
        };
    }

    // ─── SMS via BulkGate ─────────────────────────────────────────────────────

    private function sendSms(string $phone, string $message, Model $notifiable): void
    {
        $appId    = $this->tenantConfig('bulkgate_app_id');
        $appToken = $this->tenantConfig('bulkgate_app_token');
        $senderId = $this->tenantConfig('bulkgate_sender_id') ?? 'gSYS';

        if (!$appId || !$appToken) {
            $this->log('sms', $phone, $message, $notifiable, 'failed', 'bulkgate', ['error' => 'BulkGate non configurato']);
            return;
        }

        try {
            $response = Http::withBasicAuth($appId, $appToken)
                ->post('https://portal.bulkgate.com/api/2.0/simple/transactional', [
                    'application_id'    => $appId,
                    'application_token' => $appToken,
                    'number'            => $this->normalizePhone($phone),
                    'text'              => $message,
                    'sender_id'         => $senderId,
                    'unicode'           => false,
                ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $this->log('sms', $phone, $message, $notifiable, $status, 'bulkgate', $response->json());
        } catch (\Throwable $e) {
            $this->log('sms', $phone, $message, $notifiable, 'failed', 'bulkgate', ['error' => $e->getMessage()]);
        }
    }

    // ─── WhatsApp via BulkGate ────────────────────────────────────────────────

    private function sendWhatsapp(string $phone, string $message, Model $notifiable): void
    {
        $appId    = $this->tenantConfig('bulkgate_app_id');
        $appToken = $this->tenantConfig('bulkgate_app_token');

        if (!$appId || !$appToken || !$this->tenantConfig('bulkgate_whatsapp_enabled')) {
            $this->log('whatsapp', $phone, $message, $notifiable, 'failed', 'bulkgate',
                ['error' => 'WhatsApp BulkGate non abilitato']);
            return;
        }

        try {
            // BulkGate WhatsApp API endpoint
            $response = Http::withBasicAuth($appId, $appToken)
                ->post('https://portal.bulkgate.com/api/2.0/simple/whatsapp', [
                    'application_id'    => $appId,
                    'application_token' => $appToken,
                    'number'            => $this->normalizePhone($phone),
                    'text'              => $message,
                ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $this->log('whatsapp', $phone, $message, $notifiable, $status, 'bulkgate', $response->json());
        } catch (\Throwable $e) {
            $this->log('whatsapp', $phone, $message, $notifiable, 'failed', 'bulkgate', ['error' => $e->getMessage()]);
        }
    }

    // ─── Email via SMTP per-tenant ────────────────────────────────────────────

    private function sendEmail(string $email, string $message, Model $notifiable, string $name = ''): void
    {
        $host       = $this->tenantConfig('smtp_host');
        $port       = $this->tenantConfig('smtp_port') ?? 587;
        $user       = $this->tenantConfig('smtp_user');
        $password   = $this->tenantConfig('smtp_password');
        $encryption = $this->tenantConfig('smtp_encryption') ?? 'tls';
        $fromName   = $this->tenantConfig('smtp_from_name') ?? tenant('name');
        $fromEmail  = $this->tenantConfig('smtp_from_email') ?? $user;

        if (!$host || !$user) {
            $this->log('email', $email, $message, $notifiable, 'failed', 'smtp', ['error' => 'SMTP non configurato']);
            return;
        }

        try {
            // Configura mailer dinamico per il tenant
            config([
                'mail.mailers.tenant_smtp' => [
                    'transport'  => 'smtp',
                    'host'       => $host,
                    'port'       => $port,
                    'username'   => $user,
                    'password'   => $password,
                    'encryption' => $encryption,
                ],
            ]);

            Mail::mailer('tenant_smtp')
                ->send([], [], function (Message $mail) use ($email, $name, $message, $fromName, $fromEmail) {
                    $mail->to($email, $name)
                         ->from($fromEmail, $fromName)
                         ->subject($this->extractSubject($message))
                         ->html($this->messageToHtml($message));
                });

            $this->log('email', $email, $message, $notifiable, 'sent', 'smtp', []);
        } catch (\Throwable $e) {
            $this->log('email', $email, $message, $notifiable, 'failed', 'smtp', ['error' => $e->getMessage()]);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function normalizePhone(string $phone): string
    {
        // Normalizza al formato internazionale, rimuove spazi e trattini
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        // Svizzera: 07x → +417x
        if (preg_match('/^07[6-9]\d{7}$/', $phone)) {
            $phone = '+41' . substr($phone, 1);
        }
        return $phone;
    }

    private function extractSubject(string $message): string
    {
        $firstLine = strtok($message, "\n");
        return strlen($firstLine) > 80 ? substr($firstLine, 0, 77) . '...' : $firstLine;
    }

    private function messageToHtml(string $message): string
    {
        $escaped = htmlspecialchars($message, ENT_QUOTES);
        // Rende i link cliccabili
        $linked  = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" style="color:#6366f1">$1</a>',
            $escaped
        );
        return '<div style="font-family:sans-serif;font-size:15px;line-height:1.6;color:#333;white-space:pre-line">'
             . $linked . '</div>';
    }

    private function log(
        string $channel,
        string $recipient,
        string $message,
        Model $notifiable,
        string $status,
        string $provider,
        array $response,
    ): void {
        NotificationLog::create([
            'channel'           => $channel,
            'recipient'         => $recipient,
            'message'           => $message,
            'status'            => $status,
            'provider'          => $provider,
            'provider_response' => $response,
            'notifiable_type'   => get_class($notifiable),
            'notifiable_id'     => $notifiable->getKey(),
            'sent_at'           => $status === 'sent' ? now() : null,
        ]);
    }
}
