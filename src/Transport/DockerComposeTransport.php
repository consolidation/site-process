<?php

namespace Consolidation\SiteProcess\Transport;

use Consolidation\SiteProcess\SiteProcess;
use Consolidation\SiteAlias\SiteAliasInterface;
use Consolidation\SiteProcess\Util\Shell;
use Consolidation\Config\ConfigInterface;

/**
 * DockerComposeTransport knows how to wrap a command such that it executes
 * on a Docker Compose service.
 */
class DockerComposeTransport implements TransportInterface
{
    protected $tty;
    protected $siteAlias;
    protected $cd_remote;

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
     * @inheritdoc
     */
    public function wrap($args)
    {
        $transport = ['docker-compose', 'exec'];
        $transportOptions = $this->getTransportOptions();
        $commandToExecute = $this->getCommandToExecute($args);

        return array_merge(
            $transport,
            $transportOptions,
            $commandToExecute
        );
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd, $args)
    {
        $this->cd_remote = $cd;
        return $args;
    }

    /**
     * getTransportOptions returns the transport options for the tranport
     * mechanism itself
     */
    protected function getTransportOptions()
    {
        $transportOptions = [
            $this->siteAlias->get('docker.service', ''),
        ];
        if ($options = $this->siteAlias->get('docker.exec.options', '')) {
            array_unshift($transportOptions, Shell::preEscaped($options));
        }
        if (!$this->tty) {
            array_unshift($transportOptions, '-T');
        }
        if ($this->cd_remote) {
            $transportOptions = array_merge(['--workdir', $this->cd_remote], $transportOptions);
        }
        return array_filter($transportOptions);
    }

    /**
     * getCommandToExecute processes the arguments for the command to
     * be executed such that they are appropriate for the transport mechanism.
     *
     * Nothing to do for this transport.
     */
    protected function getCommandToExecute($args)
    {
        return $args;
    }
}
