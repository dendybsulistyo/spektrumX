<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResendEmailService
{
    /** @throws RequestException */
    public function send(string $to, string $subject, string $html, string $text): void
    {
        $key = config('daily-report.resend.key');
        $from = config('daily-report.resend.from');

        if (! $key || ! $from) {
            throw new RuntimeException('RESEND_API_KEY dan RESEND_FROM wajib diisi di .env.');
        }

        Http::acceptJson()
            ->withToken($key)
            ->post('https://api.resend.com/emails', [
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ])
            ->throw();
    }
}
