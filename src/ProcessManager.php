<?php

namespace Consolidation\SiteProcess;

use Psr\Log\LoggerInterface;
use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Factory\SshTransportFactory;
use Consolidation\SiteProcess\Factory\DockerComposeTransportFactory;
use Consolidation\SiteProcess\Factory\TransportFactoryInterface;
use Consolidation\SiteProcess\Transport\LocalTransport;
use Symfony\Component\Process\Process;

/**
 * ProcessManager will create a SiteProcess to run a command on a given
 * site as indicated by a SiteAlias.
 *
 * ProcessManager also manages a collection of transport factories, and
 * will produce transport instances as needed for provided site aliases.
 */
class ProcessManager
{
    protected $transportFactories = [];

    /**
     * createDefault creates a Transport manager and add the default transports to it.
     */
    public static function createDefault()
    {
        $processManager = new self();

        $processManager->add(new SshTransportFactory());
        $processManager->add(new DockerComposeTransportFactory());

        return $processManager;
    }

    /**
     * Return a site process configured with an appropriate transport
     *
     * @param AliasRecord $siteAlias Target for command
     * @param $args Command arguments
     * @param $options Associative array of command options
     * @param $optionsPassedAsArgs Associtive array of options to be passed as arguments (after double-dash)
     * @return Process
     */
    public function siteProcess(AliasRecord $siteAlias, $args = [], $options = [], $optionsPassedAsArgs = [])
    {
        $transport = $this->getTransport($siteAlias);
        $process = new SiteProcess($siteAlias, $transport, $args, $options, $optionsPassedAsArgs);
        return $process;
    }

    /**
     * add a transport factory to our factory list
     * @param TransportFactoryInterface $factory
     */
    public function add(TransportFactoryInterface $factory)
    {
        $this->transportFactories[] = $factory;
        return $this;
    }

    /**
     * hasTransport determines if there is a transport that handles the
     * provided site alias.
     *
     * @param AliasRecord $siteAlias
     * @return boolean
     */
    public function hasTransport(AliasRecord $siteAlias)
    {
        return $this->getTransportFactory($siteAlias) !== false;
    }

    /**
     * getTransport returns a transport that is applicable to the provided site alias.
     *
     * @param AliasRecord $siteAlias
     * @return TransportInterface
     */
    public function getTransport(AliasRecord $siteAlias)
    {
        $factory = $this->getTransportFactory($siteAlias);
        if ($factory) {
            return $factory->create($siteAlias);
        }
        return new LocalTransport();
    }

    /**
     * getTransportFactory returns a factory for the provided site alias.
     *
     * @param AliasRecord $siteAlias
     * @return TransportFactoryInterface
     */
    protected function getTransportFactory(AliasRecord $siteAlias)
    {
        foreach ($this->transportFactories as $factory) {
            if ($factory->check($siteAlias)) {
                return $factory;
            }
        }
        return null;
    }
}
