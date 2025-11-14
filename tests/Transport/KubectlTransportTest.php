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
                'kubectl --cluster=b --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal -- ls',
                ['ls'],
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'cluster' => 'cluster-b',
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                    ]
                ],
            ],

            // Minimal. Kubectl will pick a container.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal -- ls',
                ['ls'],
                [
                    'kubectl' => [
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                    ]
                ],
            ],

            // Don't escape arguments after "--"
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal -- asdf "double" \'single\'',
                ['asdf', '"double"', "'single'"],
                [
                    'kubectl' => [
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                    ]
                ],
            ],

            // With kubeconfig.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal --kubeconfig=/path/to/config.yaml -- ls',
                ['ls'],
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                        'kubeconfig' => '/path/to/config.yaml',
                    ]
                ],
            ],

            // With entrypoint as string.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal -- /docker-entrypoint ls',
                ['ls'],
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                        'entrypoint' => '/docker-entrypoint',
                    ]
                ],
            ],

            // With entrypoint as array.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal -- /docker-entrypoint --debug ls',
                ['ls'],
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                        'entrypoint' => ['/docker-entrypoint', '--debug'],
                    ]
                ],
            ],

            // With root.
            [
                "kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal -- sh -c 'cd /path/to/drupal && ls'",
                ['ls'],
                [
                    'root' => '/path/to/drupal',
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                    ]
                ],
            ],

            // With root and cd_root set to false.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal -- ls',
                ['ls'],
                [
                    'root' => '/path/to/drupal',
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'cd_root' => false,
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider wrapTestValues
     */
    public function testWrap($expected, $args, $siteAliasData)
    {
        $siteAlias = new SiteAlias($siteAliasData, '@alias.dev');
        $dockerTransport = new KubectlTransport($siteAlias);
        if (isset($siteAliasData['root'])) {
            $dockerTransport->addChdir($siteAliasData['root']);
        }
        $actual = $dockerTransport->wrap($args);
        $this->assertEquals($expected, implode(' ', $actual));
    }
}
