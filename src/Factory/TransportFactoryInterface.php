<?php

namespace Consolidation\SiteProcess\Factory;

use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Transport\TransportInterface;

/**
 * TransportFactoryInterface defines a transport factory that is responsible
 * for:
 *
 *  - Determining whether a provided site alias is applicable to this transport
 *  - Creating an instance of a transport for an applicable site alias.
 */
interface TransportFactoryInterface
{
    /**
     * Check to see if a provided site alias is applicable to this transport type.
     * @param AliasRecord $siteAlias
     * @return bool
     */
    public function check(AliasRecord $siteAlias);

    /**
     * Create a transport instance for an applicable site alias.
     * @param AliasRecord $siteAlias
     * @return TransportInterface
     */
    public function create(AliasRecord $siteAlias);
}
