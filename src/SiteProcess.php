<?php
namespace Consolidation\SiteProcess;

use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Util\ArgumentEngine;
use Consolidation\SiteProcess\Util\ArgumentProcessor;

class SiteProcess extends ProcessBase
{
    public function __construct(AliasRecord $siteAlias, $args, $options = [], $optionsPassedAsArgs = [])
    {
        $processor = new ArgumentProcessor();
        parent::__construct($processor->selectArgs($siteAlias, $args, $options, $optionsPassedAsArgs));
    }
}
