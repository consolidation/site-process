<?php

namespace Consolidation\SiteProcess;

use Consolidation\SiteProcess\Transport\CustomTransport;
use PHPUnit\Framework\TestCase;
use Consolidation\SiteAlias\SiteAlias;

class CustomTransportTest extends TestCase
{
    /**
     * Data provider for testWrap.
     */
    public function wrapTestValues()
    {
        return [
            [
                'ls',
                [
                    'custom' => [
                        'command' => '',
                    ],
                ],
            ],
            [
                'platform ls',
                [
                    'custom' => [
                        'command' => 'platform',
                    ],
                ],
            ],
            [
                'platform -e dev ls',
                [
                    'custom' => [
                        'command' => 'platform -e dev',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider wrapTestValues
     */
    public function testWrap($expected, $siteAliasData)
    {
        $siteAlias = new SiteAlias($siteAliasData, '@alias.dev');
        $customTransport = new CustomTransport($siteAlias);
        $actual = $customTransport->wrap(['ls']);
        $this->assertEquals($expected, implode(' ', $actual));
    }
}
