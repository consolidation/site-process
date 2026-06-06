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
                ['ls'],
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

            // With context.
            [
                'kubectl --namespace=vv exec --tty=false --stdin=false deploy/drupal --container=drupal --context=gke_my-cluster -- ls',
                ['ls'],
                [
                    'kubectl' => [
                        'tty' => false,
                        'interactive' => false,
                        'namespace' => 'vv',
                        'resource' => 'deploy/drupal',
                        'container' => 'drupal',
                        'context' => 'gke_my-cluster',
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
        ];
    }

    /**
     * @dataProvider wrapTestValues
     */
    public function testWrap($expected, $args, $siteAliasData)
    {
        $siteAlias = new SiteAlias($siteAliasData, '@alias.dev');
        $dockerTransport = new KubectlTransport($siteAlias);
        $actual = $dockerTransport->wrap($args);
        $this->assertEquals($expected, implode(' ', $actual));
    }

    /**
     * Verify that if the local system/process requests a TTY, the transport
     * automatically enables --tty and --stdin, even if they are missing
     * from the site alias YAML definition.
     */
    public function testWrapWithProcessTty()
    {
        // A minimal alias data array with NO kubectl.tty or kubectl.interactive keys
        $siteAliasData = [
            'kubectl' => [
                'namespace' => 'vv',
                'resource' => 'deploy/drupal',
            ]
        ];

        $siteAlias = new SiteAlias($siteAliasData, '@alias.dev');
        $transport = new KubectlTransport($siteAlias);

        // Mock a SiteProcess where isTty() returns true (simulating drush ssh)
        $process = $this->createMock(\Consolidation\SiteProcess\SiteProcess::class);
        $process->method('isTty')->willReturn(true);

        // Inject the mocked process configuration into the transport layer
        $transport->configure($process);

        $actual = $transport->wrap(['ls']);

        // Assert that the new || operator correctly flipped both flags to true
        $expected = 'kubectl --namespace=vv exec --tty=true --stdin=true deploy/drupal -- ls';
        $this->assertEquals($expected, implode(' ', $actual));
    }

    /**
     * Verify the new POSIX argument shifting directory wrapper.
     */
    public function testAddChdir()
    {
        $siteAlias = new SiteAlias([], '@alias.dev');
        $transport = new KubectlTransport($siteAlias);

        $initialArgs = ['drush', 'status'];
        $actualResult = $transport->addChdir('/opt/drupal', $initialArgs);

        $expectedResult = [
            '/bin/sh',
            '-c',
            'cd "$1" && shift && "$@"',
            '--',
            '/opt/drupal',
            'drush',
            'status'
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }
}
