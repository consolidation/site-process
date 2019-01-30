<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Consolidation\SiteProcess\Util\ArgumentProcessor;
use Consolidation\SiteProcess\Util\Escape;
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
                NULL,
            ],

            [
                "ls -al",
                'src',
                false,
                [],
                ['ls', '-al'],
                [],
                [],
                NULL,
            ],

            [
                "ls -al /path1 /path2",
                false,
                false,
                [],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'ls -al'",
                false,
                false,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'cd /srv/www/docroot && ls -al'",
                false,
                false,
                ['host' => 'server.net', 'user' => 'www-admin', 'root' => '/srv/www/docroot'],
                ['ls', '-al'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al'",
                'src',
                false,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'ls -al'",
                false,
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al /path1 /path2'",
                'src',
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
                NULL,
            ],

            [
                "ssh -t -o PasswordAuthentication=no www-admin@server.net 'cd src && ls -al /path1 /path2'",
                'src',
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
                NULL,
            ],

            [
                "docker-compose exec --workdir src --user root drupal ls -al /path1 /path2",
                'src',
                true,
                ['docker' => ['service' => 'drupal', 'exec' => ['options' => '--user root']]],
                ['ls', '-al', '/path1', '/path2'],
                [],
                [],
                NULL,
            ],

            [
                "drush status '--fields=root,uri'",
                false,
                false,
                [],
                ['drush', 'status'],
                ['fields' => 'root,uri'],
                [],
                'LINUX',
            ],

            [
                'drush status --fields=root,uri',
                  false,
                  false,
                  [],
                  ['drush', 'status'],
                  ['fields' => 'root,uri'],
                  [],
                  'WIN',
            ],

            [
                "drush rsync a b -- --exclude=vendor",
                false,
                false,
                [],
                ['drush', 'rsync', 'a', 'b',],
                [],
                ['exclude' => 'vendor'],
                NULL,
            ],

            [
                "drush rsync a b -- --exclude=vendor --include=vendor/autoload.php",
                false,
                false,
                [],
                ['drush', 'rsync', 'a', 'b', '--', '--include=vendor/autoload.php'],
                [],
                ['exclude' => 'vendor'],
                NULL,
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
        $optionsPassedAsArgs,
        $os)
    {
        if (Escape::isWindows(NULL) != Escape::isWindows($os)) {
          $this->markTestSkipped("OS isn't supported");
        }
        if ($useTty && Escape::isWindows($os)) {
          $this->markTestSkipped('Windows doesn\'t have /dev/tty support');
        }
        $processManager = ProcessManager::createDefault();
        $siteAlias = new AliasRecord($siteAliasData, '@alias.dev');
        $siteProcess = $processManager->siteProcess($siteAlias, $args, $options, $optionsPassedAsArgs);
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
                'LINUX',
            ],
            [
                'Unable to decode output into JSON.',
                'No json data here',
                NULL,
            ],
            [
                '{"foo":"bar"}',
                '{"foo":"bar"}',
                NULL,
            ],
            [
                '{"foo":"b\"ar"}',
                '{"foo":"b\"ar"}',
                NULL,
            ],
            [
                '{"foo":"bar"}',
                'Ignored leading data {"foo":"bar"} Ignored trailing data',
                NULL,
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
        $data,
        $os)
    {
        if (Escape::isWindows(NULL) != Escape::isWindows($os)) {
          $this->markTestSkipped("OS isn't supported");
        }
        $args = ['echo', $data];
        $processManager = ProcessManager::createDefault();
        $siteAlias = new AliasRecord([], '@alias.dev');
        $siteAlias->set('os', $os);
        $siteProcess = $processManager->siteProcess($siteAlias, $args);
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
