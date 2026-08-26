<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AccountingFixedAsset extends Model
{
    protected $table = 'accounting_fixed_assets';

    protected $fillable = [
        'nama',
        'kelompok',
        'tanggal_perolehan',
        'harga_perolehan',
        'metode',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_perolehan' => 'date',
            'harga_perolehan' => 'float',
        ];
    }

    public const KELOMPOK_LABELS = [
        'I' => 'Kelompok I (4 Tahun)',
        'II' => 'Kelompok II (8 Tahun)',
        'III' => 'Kelompok III (16 Tahun)',
        'IV' => 'Kelompok IV (20 Tahun)',
        'bangunan_permanen' => 'Bangunan Permanen (20 Tahun)',
        'bangunan_semi' => 'Bangunan Semi-Permanen (10 Tahun)',
    ];

    /**
     * Get useful life (years) and straight-line rate for the given kelompok.
     */
    public static function getRulesForKelompok(string $kelompok): array
    {
        return match ($kelompok) {
            'I' => ['years' => 4, 'rate' => 0.25],
            'II' => ['years' => 8, 'rate' => 0.125],
            'III' => ['years' => 16, 'rate' => 0.0625],
            'IV' => ['years' => 20, 'rate' => 0.05],
            'bangunan_permanen' => ['years' => 20, 'rate' => 0.05],
            'bangunan_semi' => ['years' => 10, 'rate' => 0.10],
            default => ['years' => 4, 'rate' => 0.25],
        };
    }

    /**
     * Calculate depreciation figures for a target calendar year.
     * Returns an array with:
     * - rate: straight line rate
     * - years: useful life
     * - prior_months: months of usage before target year
     * - prior_depreciation: accumulated depreciation before target year
     * - current_months: months of usage during target year
     * - current_depreciation: depreciation expense for target year
     * - accumulated_depreciation: total accumulated depreciation at end of target year
     * - book_value: net book value at end of target year
     */
    public function calculateDepreciationForYear(int $targetYear): array
    {
        $rules = self::getRulesForKelompok($this->kelompok);
        $years = $rules['years'];
        $rate = $rules['rate'];
        $maxMonths = $years * 12;

        $acqDate = $this->tanggal_perolehan;
        $acqYear = $acqDate->year;
        $acqMonth = $acqDate->month;

        $priorMonths = 0;
        $currentMonths = 0;

        if ($targetYear < $acqYear) {
            // Asset not acquired yet
            $priorMonths = 0;
            $currentMonths = 0;
        } elseif ($targetYear == $acqYear) {
            // Acquired during target year
            $priorMonths = 0;
            $currentMonths = min(12 - $acqMonth + 1, $maxMonths);
        } else {
            // Acquired before target year
            // Total months of life from acquisition month to Dec of the year before targetYear
            $monthsElapsedBefore = ($targetYear - $acqYear) * 12 - ($acqMonth - 1);
            $priorMonths = min($monthsElapsedBefore, $maxMonths);
            
            $remainingMonths = $maxMonths - $priorMonths;
            $currentMonths = min(12, $remainingMonths);
        }

        // Calculate depreciation using robust rounding to prevent decimal leakage
        $priorDep = round(($this->harga_perolehan * $priorMonths) / $maxMonths);
        $totalDepAtEnd = round(($this->harga_perolehan * ($priorMonths + $currentMonths)) / $maxMonths);
        $currentDep = $totalDepAtEnd - $priorDep;

        $bookValue = $this->harga_perolehan - $totalDepAtEnd;

        return [
            'rate' => $rate,
            'years' => $years,
            'prior_months' => $priorMonths,
            'prior_depreciation' => $priorDep,
            'current_months' => $currentMonths,
            'current_depreciation' => $currentDep,
            'accumulated_depreciation' => $totalDepAtEnd,
            'book_value' => $bookValue,
        ];
    }
}
