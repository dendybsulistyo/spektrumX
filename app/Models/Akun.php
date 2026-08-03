<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The legacy chart of accounts (`am__`) — pre-populated with a standard
 * Indonesian COA (Aktiva/Kewajiban/Ekuitas/Pendapatan/Beban), never wired up
 * to anything until the accounting integration. TipeDK is the account's
 * normal balance side (D/K), TipeNL is N (Neraca/balance sheet) or L
 * (Laba-Rugi/income statement) — header/group rows (e.g. "11000 Aktiva
 * Lancar") have '-' for both and aren't postable.
 */
class Akun extends Model
{
    protected $table = 'am__';

    protected $primaryKey = 'NoAkun';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['NoAkun', 'NmAkun', 'TipeDK', 'TipeNL'];

    public function isLabaRugi(): bool
    {
        return $this->TipeNL === 'L';
    }
}
