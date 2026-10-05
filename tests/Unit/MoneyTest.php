<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_calculations_do_not_accumulate_float_errors(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('999999999999.99', Money::sub('1000000000000.00', '0.01'));
        $this->assertSame('0.21', Money::mul('0.07', 3));
    }
}
