<?php

namespace Consolidation\SiteProcess;

use Psr\Log\LoggerInterface;
use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Factory\SshTransportFactory;
use Consolidation\SiteProcess\Factory\DockerComposeTransportFactory;
use Consolidation\SiteProcess\Factory\TransportFactoryInterface;
use Consolidation\SiteProcess\Transport\LocalTransport;

/**
 * TransportManager manages a collection of transport factories, and
 * will produce transport instances as needed for provided site aliases.
 */
class TransportManager
{
    protected $transportFactories = [];

    /**
     * createDefault creates a Transport manager and add the default transports to it.
     */
    public static function createDefault()
    {
        $transportManager = new self();

        $transportManager->add(new SshTransportFactory());
        $transportManager->add(new DockerComposeTransportFactory());

        return $transportManager;
    }

    /**
     * add a transport factory to our factory list
     * @param TransportFactoryInterface $factory
     */
    public function add(TransportFactoryInterface $factory)
    {
        $this->transportFactories[] = $factory;
    }

    /**
     * hasTransport determines if there is a transport that handles the
     * provided site alias.
     */
    public function hasTransport(AliasRecord $siteAlias)
    {
        return $this->getTransportFactory($siteAlias) !== false;
    }

    /**
     * getTransport returns a transport that is applicable to the provided site alias.
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
     */
    protected function getTransportFactory(AliasRecord $siteAlias)
    {
        foreach ($this->transportFactories as $factory) {
            if ($factory->check($siteAlias)) {
                return $factory;
            }
        }
        return false;
    }
}
