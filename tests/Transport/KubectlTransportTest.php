<?php

namespace Consolidation\SiteProcess;

use Consolidation\SiteProcess\Transport\KubectlTransport;
use PHPUnit\Framework\TestCase;
use Consolidation\SiteAlias\SiteAlias;

class KubectlTransportTest extends TestCase
{
    /**
     * Data provider for testWrap.
     */
    public function wrapTestValues()
    {
        return [
            // Everything explicit.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal -- ls',
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                    ]
                ],
            ],

            // Minimal. Kubectl will pick a container.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal -- ls',
                [
                    'kubectl' => [
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
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
        $dockerTransport = new KubectlTransport($siteAlias);
        $actual = $dockerTransport->wrap(['ls']);
        $this->assertEquals($expected, implode(' ', $actual));
    }
}
