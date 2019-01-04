<?php

namespace Consolidation\SiteProcess\Factory;

use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Transport\SshTransport;

/**
 * SshTransportFactory will create an SshTransport for applicable site aliases.
 */
class SshTransportFactory implements TransportFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function check(AliasRecord $siteAlias)
    {
        // TODO: deprecate and eventually remove 'isRemote()', and move the logic here.
        return $siteAlias->isRemote();
    }

    /**
     * @inheritdoc
     */
    public function create(AliasRecord $siteAlias)
    {
        return new SshTransport($siteAlias);
    }
}
