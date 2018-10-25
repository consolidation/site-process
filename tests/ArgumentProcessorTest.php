<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Consolidation\SiteProcess\Util\ArgumentProcessor;
use Consolidation\SiteAlias\AliasRecord;

class ArgumentProcessorTest extends TestCase
{
    /**
     * Data provider for testArgumentProcessor.
     */
    public function argumentProcessorTestValues()
    {
        return CommonTestDataFixtures::argumentTestValues();
    }

    /**
     * Test the SiteProcess class.
     *
     * @dataProvider argumentProcessorTestValues
     */
    public function testArgumentProcessor(
        $expected,
        $ignoredExpectedForSiteProcessTest,
        $useTty,
        $siteAliasData,
        $args,
        $options,
        $optionsPassedAsArgs)
    {
        $siteAlias = new AliasRecord($siteAliasData, '@alias.dev');
        $processor = new ArgumentProcessor();

        if ($useTty) {
            $this->markTestSkipped('Tty tests not applicable to argument processor.');
        }

        $actual = $processor->selectArgs($siteAlias, $args, $options, $optionsPassedAsArgs);
        $actual = '["' . implode('", "', $actual) . '"]';
        $this->assertEquals($expected, $actual);
    }
}
