<?php
namespace Consolidation\SiteProcess;

use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Util\ArgumentEngine;

class SiteProcess extends ProcessBase
{
    public function __construct(AliasRecord $siteAlias, $args, $options = [])
    {
        $argumentEngine = new ArgumentEngine();
        parent::__construct($argumentEngine->selectArgs($siteAlias, $args, $options));
    }
}
