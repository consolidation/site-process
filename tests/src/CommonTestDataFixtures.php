<?php

namespace Consolidation\SiteProcess;

class CommonTestDataFixtures
{
    /**
     * Fixtures used to test both ArgumentProcessor and SiteProcess.
     *
     * The first expected value is the expected result from ArgumentProcessor,
     * and the second expected value is the expected result from ArgumentProcessor.
     */
    public static function argumentTestValues()
    {
        return [
            [
                '["ls", "-al"]',
                "'ls' '-al'",
                false,
                [],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                '["ssh", "-o PasswordAuthentication=no", "www-admin@server.net", "ls -al"]',
                "'ssh' '-o PasswordAuthentication=no' 'www-admin@server.net' 'ls -al'",
                false,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                '["ssh", "-o PasswordAuthentication=no", "www-admin@server.net", "ls -al"]',
                "'ssh' -t '-o PasswordAuthentication=no' 'www-admin@server.net' 'ls -al'",
                true,
                ['host' => 'server.net', 'user' => 'www-admin'],
                ['ls', '-al'],
                [],
                [],
            ],

            [
                '["drush", "status", "--fields=root,uri"]',
                "'drush' 'status' '--fields=root,uri'",
                false,
                [],
                ['drush', 'status'],
                ['fields' => 'root,uri'],
                [],
            ],

            [
                '["drush", "rsync", "a", "b", "--", "--exclude=vendor"]',
                "'drush' 'rsync' 'a' 'b' '--' '--exclude=vendor'",
                false,
                [],
                ['drush', 'rsync', 'a', 'b',],
                [],
                ['exclude' => 'vendor'],
            ],

            [
                '["drush", "rsync", "a", "b", "--", "--exclude=vendor", "--include=vendor/autoload.php"]',
                "'drush' 'rsync' 'a' 'b' '--' '--exclude=vendor' '--include=vendor/autoload.php'",
                false,
                [],
                ['drush', 'rsync', 'a', 'b', '--', '--include=vendor/autoload.php'],
                [],
                ['exclude' => 'vendor'],
            ],
        ];
    }
}
