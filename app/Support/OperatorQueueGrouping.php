<?php

namespace App\Support;

use Illuminate\Http\Request;

class OperatorQueueGrouping
{
    public static function resolve(Request $request, string $queue): string
    {
        $groupBy = $request->string('group_by')->toString();

        if (in_array($groupBy, ['order', 'division', 'product'], true)) {
            $request->session()->put("operator-queue.{$queue}.group-by", $groupBy);

            return $groupBy;
        }

        return $request->session()->get("operator-queue.{$queue}.group-by", 'order');
    }
}
