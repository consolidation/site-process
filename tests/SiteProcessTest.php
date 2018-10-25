<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Consolidation\SiteProcess\Util\ArgumentProcessor;
use Consolidation\SiteAlias\AliasRecord;

class SiteProcessTest extends TestCase
{
    /**
     * Data provider for testSiteProcess.
     */
    public function siteProcessTestValues()
    {
        return CommonTestDataFixtures::argumentTestValues();
    }

    /**
     * Test the SiteProcess class.
     *
     * @dataProvider siteProcessTestValues
     */
    public function testSiteProcess(
        $ignoredExpectedForArgumentProcessorTest,
        $expected,
        $useTty,
        $siteAliasData,
        $args,
        $options,
        $optionsPassedAsArgs)
    {
        $siteAlias = new AliasRecord($siteAliasData, '@alias.dev');
        $siteProcess = new SiteProcess($siteAlias, $args, $options, $optionsPassedAsArgs);
        $siteProcess->setTty($useTty);

        $actual = $siteProcess->getCommandLine();
        $this->assertEquals($expected, $actual);
    }
}
