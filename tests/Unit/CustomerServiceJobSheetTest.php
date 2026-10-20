<?php

namespace Tests\Unit;

use App\Models\CustomerServiceJobSheet;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerServiceJobSheetTest extends TestCase
{
    public function test_estimate_ends_after_selected_number_of_days(): void
    {
        $sheet = new CustomerServiceJobSheet([
            'progress_hari' => 2,
        ]);
        $sheet->created_at = Carbon::parse('2026-10-20 08:30:00', 'Asia/Jakarta');

        $this->assertSame(
            '2026-10-22 08:30:00',
            $sheet->estimateEndsAt()?->format('Y-m-d H:i:s'),
        );
    }

    public function test_estimate_is_absent_for_legacy_sheet_without_progress_days(): void
    {
        $sheet = new CustomerServiceJobSheet;
        $sheet->created_at = Carbon::parse('2026-10-20 08:30:00', 'Asia/Jakarta');

        $this->assertNull($sheet->estimateEndsAt());
    }
}
