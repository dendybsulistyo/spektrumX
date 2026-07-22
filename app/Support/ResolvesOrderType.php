<?php

namespace App\Support;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Database\Eloquent\Model;

trait ResolvesOrderType
{
    protected function resolveOrder(string $type, int $id): Model
    {
        return match ($type) {
            'indoor' => OrderIndoor::findOrFail($id),
            'outdoor' => OrderOutdoor::findOrFail($id),
            'artwork' => OrderArtwork::findOrFail($id),
            default => abort(404),
        };
    }
}
