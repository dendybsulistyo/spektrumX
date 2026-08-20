<?php

namespace App\Console\Commands;

use App\Services\DailyTransactionReportService;
use App\Services\ResendEmailService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use RuntimeException;

class SendDailyTransactionReport extends Command
{
    protected $signature = 'report:send-daily {--date= : Tanggal laporan (YYYY-MM-DD); default hari ini} {--to= : Override email penerima dari .env}';

    protected $description = 'Kirim rekap transaksi harian ke owner melalui Resend';

    public function handle(DailyTransactionReportService $reportService, ResendEmailService $resend): int
    {
        $timezone = config('daily-report.timezone');
        $date = $this->option('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('date'), $timezone)
            : now($timezone)->toImmutable();
        $recipient = $this->option('to') ?: config('daily-report.recipient');

        if (! $recipient) {
            throw new RuntimeException('DAILY_REPORT_TO wajib diisi di .env atau gunakan opsi --to=.');
        }

        $report = $reportService->forDate($date);
        $titleDate = $report['date']->locale('id')->translatedFormat('l, d F Y');
        $subject = "Laporan Harian Spektrum ({$titleDate})";
        $lines = [
            "Laporan Harian Spektrum ({$titleDate}):",
            '',
            'Omzet : '.$this->rupiah($report['omzet']).' dari '.$report['nota_count'].' nota',
            '',
            'Tunai '.$this->rupiah($report['tunai']),
            'QRIS '.$this->rupiah($report['qris']),
            'Transfer '.$this->rupiah($report['transfer']),
            'DP '.$this->rupiah($report['dp']),
            '',
            'Piutang baru : '.$this->rupiah($report['piutang_baru']),
            'Pelunasan piutang : '.$this->rupiah($report['pelunasan_piutang']),
            'Diskon '.$this->rupiah($report['diskon']),
            'Refund '.$this->rupiah($report['refund']),
        ];
        $text = implode("\n", $lines);
        $cell = fn (string $label, float $nominal) => '<td style="width:50%;padding:12px 14px;border:1px solid #e5e7eb">'
            .'<div style="color:#6b7280;font-size:12px">'.e($label).'</div>'
            .'<div style="margin-top:3px;font-size:17px;font-weight:700;color:#111827">'.$this->rupiah($nominal).'</div>'
            .'</td>';
        $html = '<div style="max-width:640px;font-family:Arial,sans-serif;color:#1f2937;line-height:1.5">'
            .'<h2 style="margin:0 0 4px;font-size:20px">Laporan Harian Spektrum</h2>'
            .'<p style="margin:0 0 20px;color:#6b7280">'.e($titleDate).'</p>'
            .'<div style="margin-bottom:16px;padding:16px;background:#eef2ff;border-radius:8px">'
            .'<div style="color:#4f46e5;font-size:12px;font-weight:700;text-transform:uppercase">Omzet</div>'
            .'<div style="margin-top:3px;font-size:22px;font-weight:700">'.$this->rupiah($report['omzet']).'</div>'
            .'<div style="margin-top:2px;color:#4b5563;font-size:13px">dari '.$report['nota_count'].' nota</div>'
            .'</div>'
            .'<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse">'
            .'<tr>'.$cell('Tunai', $report['tunai']).$cell('QRIS', $report['qris']).'</tr>'
            .'<tr>'.$cell('Transfer', $report['transfer']).$cell('DP', $report['dp']).'</tr>'
            .'<tr>'.$cell('Piutang baru', $report['piutang_baru']).$cell('Pelunasan piutang', $report['pelunasan_piutang']).'</tr>'
            .'<tr>'.$cell('Diskon', $report['diskon']).$cell('Refund', $report['refund']).'</tr>'
            .'</table></div>';

        $resend->send($recipient, $subject, $html, $text);

        $this->info("Laporan {$report['date']->format('Y-m-d')} terkirim ke {$recipient}.");

        return self::SUCCESS;
    }

    private function rupiah(float $nominal): string
    {
        return 'Rp'.number_format($nominal, 0, ',', '.');
    }
}
