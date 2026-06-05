<?php

namespace App\Tests\Unit\Service\Admin;

use App\Service\Admin\PaginationHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests du helper de pagination admin.
 */
class PaginationHelperTest extends TestCase
{
    public function testNormalizeClampsPageToValidRange(): void
    {
        $meta = PaginationHelper::normalize(99, 20, 45);

        $this->assertSame(3, $meta['page']);
        $this->assertSame(3, $meta['pages']);
        $this->assertSame(40, $meta['offset']);
    }

    public function testPageRangeReturnsWindow(): void
    {
        $range = PaginationHelper::pageRange(5, 20, 2);

        $this->assertSame([3, 4, 5, 6, 7], $range);
    }
}
