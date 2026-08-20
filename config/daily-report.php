<?php

return [
    'recipient' => env('DAILY_REPORT_TO'),
    'time' => env('DAILY_REPORT_TIME', '21:00'),
    'timezone' => env('DAILY_REPORT_TIMEZONE', 'Asia/Jakarta'),
    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'from' => env('RESEND_FROM'),
    ],
];
