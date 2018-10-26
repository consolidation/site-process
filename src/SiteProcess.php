<?php
namespace Consolidation\SiteProcess;

use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Util\ArgumentProcessor;

/**
 * A wrapper around Symfony Process that uses site aliases
 * (https://github.com/consolidation/site-alias)
 *
 * - Interpolate arguments using values from the alias
 *   e.g. `$process = new SiteProcess($alias, ['git', '-C', '{{root}}']);`
 * - Make remote calls via ssh as if they were local.
 */
class SiteProcess extends ProcessBase
{
    /**
     * Process arguments and options per the site alias and build the
     * actual command to run.
     */
    public function __construct(AliasRecord $siteAlias, $args, $options = [], $optionsPassedAsArgs = [])
    {
        $processor = new ArgumentProcessor();
        $processedArgs = $processor->selectArgs($siteAlias, $args, $options, $optionsPassedAsArgs);
        parent::__construct($processedArgs);
    }

    /**
     * @inheritDoc
     */
    public function getCommandLine()
    {
        $commandLine = parent::getCommandLine();
        if ($this->isTty()) {
            $commandLine = preg_replace('#^([^a-z]*)ssh([^a-z ]*)#', '\1ssh\2 -t', $commandLine);
            $this->setCommandLine($commandLine);
        }
        return $commandLine;
    }
}
