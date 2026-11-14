<?php

namespace Tests\Unit;

use App\Fire\Bulletin;
use PHPUnit\Framework\TestCase;

class BulletinGuardTest extends TestCase
{
    private array $facts = ['area' => 'Dominican Republic', 'unit' => 'burning 1 km cell-days', 'week' => ['starts' => 'April 27, 2015', 'burning_cell_days' => 1236, 'typical_for_this_week' => 107, 'times_normal' => 11.5, 'rank_among_same_week' => 1, 'years_compared' => 26]];

    public function test_accepts_text_that_only_uses_the_facts(): void
    {
        $this->assertTrue(Bulletin::onlyKnownNumbers('In the week of April 27, 2015 the Dominican Republic had 1,236 burning 1 km cell-days, 11.5 times normal (107). It ranks 1 of 26 years.', $this->facts));
        $this->assertTrue(Bulletin::onlyKnownNumbers('La semana del 27 de abril de 2015 tuvo 1.236 celdas-día, 11,5 veces lo normal.', $this->facts));
    }

    public function test_rejects_any_invented_number(): void
    {
        $this->assertFalse(Bulletin::onlyKnownNumbers('It was 12 times normal.', $this->facts));
        $this->assertFalse(Bulletin::onlyKnownNumbers('About 40% of the year burned.', $this->facts));
    }

    public function test_template_reads_the_facts(): void
    {
        $text = Bulletin::template($this->facts + ['season' => ['start' => 'March', 'peak' => 'March', 'end' => 'April', 'share_of_year_percent' => 44]], 'en');
        $this->assertStringContainsString('11.5 times normal', $text);
        $this->assertTrue(Bulletin::onlyKnownNumbers($text, $this->facts));
    }
}
