<?php

namespace Tests\Unit;

use App\Support\OutdoorNestingPlanner;
use PHPUnit\Framework\TestCase;

class OutdoorNestingPlannerTest extends TestCase
{
    public function test_roll_width_is_read_from_material_name(): void
    {
        $this->assertSame(320.0, OutdoorNestingPlanner::rollWidthFromName('FT China 280-3.2'));
        $this->assertSame(510.0, OutdoorNestingPlanner::rollWidthFromName('BLSS Korea 610-5.1'));
        $this->assertSame(152.0, OutdoorNestingPlanner::rollWidthFromName('White Gloss 80-1.52'));
        $this->assertSame(320.0, OutdoorNestingPlanner::rollWidthFromName('FL Coated 510gsm 3.2'));
        $this->assertSame(510.0, OutdoorNestingPlanner::rollWidthFromName('FL Coated 510gsm 5.1m'));
        $this->assertSame(155.0, OutdoorNestingPlanner::rollWidthFromName('Ri/Av WhiteGloss AP BO 1.55m'));
        $this->assertSame(200.0, OutdoorNestingPlanner::rollWidthFromName('RI/AV White Gloss L2-2.0'));
        $this->assertNull(OutdoorNestingPlanner::rollWidthFromName('RI/AV White Gloss 100'));
        $this->assertNull(OutdoorNestingPlanner::rollWidthFromName('Banner Cloth'));
    }

    public function test_pieces_share_a_shelf_side_by_side(): void
    {
        $plan = (new OutdoorNestingPlanner)->plan(320, [
            ['key' => 'a', 'label' => 'A', 'width' => 150, 'height' => 80, 'qty' => 1],
            ['key' => 'b', 'label' => 'B', 'width' => 160, 'height' => 80, 'qty' => 1],
        ], margin: 0, gap: 5);

        $this->assertCount(1, $plan['shelves']);
        $this->assertSame(80.0, (float) $plan['length']);
        $this->assertEqualsWithDelta((150 * 80 + 160 * 80) / (320 * 80), $plan['efficiency'], 0.0001);
    }

    public function test_long_side_is_rotated_across_the_roll_and_overflow_starts_new_shelf(): void
    {
        $plan = (new OutdoorNestingPlanner)->plan(320, [
            ['key' => 'a', 'label' => 'A', 'width' => 100, 'height' => 300, 'qty' => 2],
        ], margin: 10, gap: 5);

        // 300 cm melintang roll (lebar efektif 300), dua lembar → dua rak 100 cm.
        $this->assertCount(2, $plan['shelves']);
        $this->assertTrue($plan['shelves'][0]['pieces'][0]['rotated']);
        $this->assertSame(10.0 + 100 + 5 + 100 + 10, (float) $plan['length']);
    }

    public function test_pieces_wider_than_roll_are_reported(): void
    {
        $plan = (new OutdoorNestingPlanner)->plan(152, [
            ['key' => 'a', 'label' => 'A', 'width' => 400, 'height' => 200, 'qty' => 1],
        ]);

        $this->assertSame([], $plan['shelves']);
        $this->assertSame('a', $plan['too_wide'][0]['key']);
    }
}
