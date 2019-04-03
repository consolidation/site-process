<?php

namespace Consolidation\SiteProcess;

use Consolidation\SiteProcess\Transport\DockerComposeTransport;
use PHPUnit\Framework\TestCase;
use Consolidation\SiteAlias\SiteAlias;

class DockerComposeTransportTest extends TestCase
{
    /**
     * Data provider for testWrap.
     */
    public function wrapTestValues()
    {
        return [
            [
                'docker-compose --project project --project-directory projectDir --file myCompose.yml exec -T --user root drupal ls',
                [
                    'docker' => [
                        'service' => 'drupal',
                        'compose' => [
                            'options' => '--project project --project-directory projectDir --file myCompose.yml'
                        ],
                        'file' => 'docker-compose.yml',
                        'exec' => ['options' => '--user root']
                    ]
                ],
            ],
            [
                'docker-compose exec -T drupal ls',
                [
                    'docker' => [
                        'service' => 'drupal',
                    ]
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
        $dockerTransport = new DockerComposeTransport($siteAlias);
        $actual = $dockerTransport->wrap(['ls']);
        $this->assertEquals($expected, implode(' ', $actual));
    }
}
