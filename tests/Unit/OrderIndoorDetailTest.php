<?php

namespace Tests\Unit;

use App\Models\OrderIndoorDetail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderIndoorDetailTest extends TestCase
{
    public static function flatbedDescriptions(): array
    {
        return [
            'CMYK' => ['Oscar Putih (Print CMYK) 43 cm'],
            'CYMK typo' => ['Oscar Putih (Print CYMK) 43 cm'],
            'case insensitive' => ['print cmyk'],
        ];
    }

    #[DataProvider('flatbedDescriptions')]
    public function test_print_cmyk_is_routed_to_flatbed(string $description): void
    {
        $item = new OrderIndoorDetail(['Judul' => $description]);

        $this->assertSame('Print Flatbed', $item->divisionName());
    }
}
