<?php

namespace Consolidation\SiteProcess\Factory;

use Consolidation\SiteAlias\AliasRecordInterface;
use Consolidation\SiteProcess\Transport\SshTransport;
use Consolidation\Config\ConfigInterface;

/**
 * SshTransportFactory will create an SshTransport for applicable site aliases.
 */
class SshTransportFactory implements TransportFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function check(AliasRecordInterface $siteAlias)
    {
        // TODO: deprecate and eventually remove 'isRemote()', and move the logic here.
        return $siteAlias->isRemote();
    }

    /**
     * @inheritdoc
     */
    public function create(AliasRecordInterface $siteAlias)
    {
        return new SshTransport($siteAlias);
    }
}
