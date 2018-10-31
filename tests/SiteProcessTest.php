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
        return [
            [
                "ls -al",
                false,
                false,
                [],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ls -al",
                'src',
                false,
                [],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ls -al /path1 /path2",
                false,
                false,
                [],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'ls -al'",
                false,
                false,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'cd /srv/www/docroot && ls -al'",
                false,
                false,
                ['host' => 'server.net', 'user' => 'www-admin', 'root' => '/srv/www/docroot'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al'",
                'src',
                false,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'ls -al'",
                false,
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al /path1 /path2'",
                'src',
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al /path1 /path2'",
                'src',
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
            ],

            [
                "drush status '--fields=root,uri'",
                false,
                false,
                [],
                ['drush', 'status'],
                ['fields' => 'root,uri'],
                [],
            ],

            [
                "drush rsync a b -- --exclude=vendor",
                false,
                false,
                [],
                ['drush', 'rsync', 'a', 'b',],
                [],
                ['exclude' => 'vendor'],
            ],

            [
                "drush rsync a b -- --exclude=vendor --include=vendor/autoload.php",
                false,
                false,
                [],
                ['drush', 'rsync', 'a', 'b', '--', '--include=vendor/autoload.php'],
                [],
                ['exclude' => 'vendor'],
            ],
        ];
    }

    /**
     * Test the SiteProcess class.
     *
     * @dataProvider siteProcessTestValues
     */
    public function testSiteProcess(
        $expected,
        $cd,
        $useTty,
        $siteAliasData,
        $args,
        $options,
        $optionsPassedAsArgs)
    {
        $siteAlias = new AliasRecord($siteAliasData, '@alias.dev');
        $siteProcess = new SiteProcess($siteAlias, $args, $options, $optionsPassedAsArgs);
        $siteProcess->setTty($useTty);
        if ($cd) {
            $siteProcess->setWorkingDirectory($cd);
        }
        else {
            $siteProcess->chdirToSiteRoot();
        }

        $actual = $siteProcess->getCommandLine();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testSiteProcessJson.
     */
    public function siteProcessJsonTestValues()
    {
        return [
            [
                'Output is empty.',
                '',
            ],
            [
                'Unable to decode output into JSON.',
                'No json data here',
            ],
            [
                '{"foo":"bar"}',
                '{"foo":"bar"}',
            ],
            [
                '{"foo":"bar"}',
                'Ignored leading data {"foo":"bar"} Ignored trailing data',
            ],
        ];
    }

    /**
     * Test the SiteProcess class.
     *
     * @dataProvider siteProcessJsonTestValues
     */
    public function testSiteProcessJson(
        $expected,
        $data)
    {
        $args = ['echo', $data];
        $siteAlias = new AliasRecord([], '@alias.dev');
        $siteProcess = new SiteProcess($siteAlias, $args);
        $siteProcess->mustRun();

        try {
            $actual = $siteProcess->getOutputAsJson();
            $actual = json_encode($actual, true);
        }
        catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
