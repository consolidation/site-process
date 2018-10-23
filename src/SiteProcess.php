<?php
namespace Consolidation\SiteProcess;

use Consolidation\SiteAlias\AliasRecord;
use SiteProcess\Util\ArgumentEngine;
use Symfony\Component\Process\Process;

class SiteProcess extends Process
{
    public function __construct(AliasRecord $siteAlias, $args, $options = [])
    {
        $argumentEngine = new ArgumentEngine();
        parent::construct($argumentEngine->selectArgs($siteAlias, $args, $options));
    }
}
