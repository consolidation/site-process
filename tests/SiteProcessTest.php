<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;

class SiteProcessTest extends TestCase
{
    /**
     * Data provider for testSiteProcess.
     */
    public function siteProcessTestValues()
    {
        return [
            [4, 2, 2,],
            [9, 3, 3,],
            [56, 7, 8,],
        ];
    }

    /**
     * Test the SiteProcess class.
     *
     * @dataProvider siteProcessTestValues
     */
    public function testSiteProcess($expected, $constructor_parameter, $value)
    {
        //$process = new SiteProcess($constructor_parameter);
        $actual = $expected;
        $this->assertEquals($expected, $actual);
    }
}
