<?php

namespace Consolidation\SiteProcess\Transport;

use Consolidation\SiteProcess\SiteProcess;
use Consolidation\SiteAlias\SiteAliasInterface;
use Consolidation\SiteProcess\Util\Escape;
use Consolidation\SiteProcess\Util\Shell;

/**
 * KubectlTransport knows how to wrap a command such that it runs in a container
 * on Kubernetes via kubectl.
 */
class KubectlTransport implements TransportInterface
{
    /** @var bool */
    protected $tty;

    /** @var string */
    protected $cd_remote;

    /** @var \Consolidation\SiteAlias\SiteAliasInterface */
    protected $siteAlias;

    public function __construct(SiteAliasInterface $siteAlias)
    {
        $this->siteAlias = $siteAlias;
    }

    /**
     * @inheritdoc
     */
    public function configure(SiteProcess $process)
    {
        $this->tty = $process->isTty();
    }

    /**
     * inheritdoc
     */
    public function wrap($args)
    {
        # TODO: How/where do we complain if a required argument is not available?
        $namespace = $this->siteAlias->get('kubectl.namespace');
        $tty = $this->tty && $this->siteAlias->get('kubectl.tty', false) ? "true" : "false";
        $interactive = $this->tty && $this->siteAlias->get('kubectl.interactive', false) ? "true" : "false";
        $resource = $this->siteAlias->get('kubectl.resource');
        $container = $this->siteAlias->get('kubectl.container');
        $kubeconfig = $this->siteAlias->get('kubectl.kubeconfig');
        $entrypoint = $this->siteAlias->get('kubectl.entrypoint');

        $transport = [
            'kubectl',
            "--namespace=$namespace",
            'exec',
            "--tty=$tty",
            "--stdin=$interactive",
            $resource,
        ];
        if ($container) {
            $transport[] = "--container=$container";
        }
        if ($kubeconfig) {
            $transport[] = "--kubeconfig=$kubeconfig";
        }
        $transport[] = "--";
        if ($entrypoint) {
            $transport = is_array($entrypoint) ? [...$transport, ...$entrypoint] : [...$transport, $entrypoint];
        }

        if ($this->cd_remote) {
            // Wrap the command in a subshell, to be able to prepend a `cd`.
            $args = [
                'sh',
                '-c',
                // Escape each argument for the target system and then join.
                implode(' ', Escape::argsForSite($this->siteAlias, [
                    'cd',
                    $this->cd_remote,
                    Shell::op('&&'),
                    ...$args
                ]))
            ];
        }

        return array_merge($transport, $args);
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd_remote, $args)
    {
        // If the site alias specifies a root, and it matches the requested
        // directory, there is no need to wrap the command in a subshell.
        if ($cd_remote === $this->siteAlias->get('root') && $this->siteAlias->get('kubectl.cd_root') === false) {
            return $args;
        }

        $this->cd_remote = $cd_remote;
        return $args;
    }
}
