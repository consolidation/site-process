<?php

namespace Consolidation\SiteProcess\Factory;

use Consolidation\SiteAlias\AliasRecordInterface;
use Consolidation\SiteProcess\Transport\TransportInterface;
use Consolidation\Config\ConfigInterface;

/**
 * TransportFactoryInterface defines a transport factory that is responsible
 * for:
 *
 *  - Determining whether a provided site alias is applicable to this transport
 *  - Creating an instance of a transport for an applicable site alias.
 *
 * There is always a transport for every factory, and visa-versa.
 * @see Consolidation\SiteProcess\Transport\TransportInterface
 */
interface TransportFactoryInterface
{
    /**
     * Check to see if a provided site alias is applicable to this transport type.
     * @param AliasRecordInterface $siteAlias
     * @return bool
     */
    public function check(AliasRecordInterface $siteAlias);

    /**
     * Create a transport instance for an applicable site alias.
     * @param AliasRecordInterface $siteAlias
     * @return TransportInterface
     */
    public function create(AliasRecordInterface $siteAlias);
}
