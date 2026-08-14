<?php

namespace App\Support;

use App\Models\OrderArtworkDetail;
use App\Models\OrderIndoorDetail;
use App\Models\OrderOutdoorDetail;
use Illuminate\Database\Eloquent\Model;

trait ResolvesOrderDetailType
{
    protected function resolveDetailItem(string $type, int $id): Model
    {
        return match ($type) {
            'indoor' => OrderIndoorDetail::findOrFail($id),
            'outdoor' => OrderOutdoorDetail::findOrFail($id),
            'artwork' => OrderArtworkDetail::findOrFail($id),
            default => abort(404),
        };
    }

    /**
     * @return class-string<Model>
     */
    protected function detailModelClass(string $type): string
    {
        return match ($type) {
            'indoor' => OrderIndoorDetail::class,
            'outdoor' => OrderOutdoorDetail::class,
            'artwork' => OrderArtworkDetail::class,
            default => abort(404),
        };
    }
}
