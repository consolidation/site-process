<?php

namespace Consolidation\SiteProcess\Factory;

use Consolidation\SiteAlias\SiteAliasInterface;
use Consolidation\SiteProcess\Transport\CustomTransport;
use Consolidation\Config\ConfigInterface;

/**
 * CustomTransportFactory will create an CustomTransport for applicable site aliases.
 */
class CustomTransportFactory implements TransportFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function check(SiteAliasInterface $siteAlias)
    {
        return $siteAlias->has('command');
    }

    /**
     * @inheritdoc
     */
    public function create(SiteAliasInterface $siteAlias)
    {
        return new CustomTransport($siteAlias);
    }
}
