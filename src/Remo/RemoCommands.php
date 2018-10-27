<?php

namespace Consolidation\SiteProcess\Remo;

use Consolidation\SiteProcess\SiteProcess;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;

class RemoCommands extends \Robo\Tasks
{
    use SiteAliasManagerAwareTrait;

    /**
     * Run a command identified by a site alias
     *
     * @command run
     */
    public function run($aliasName, array $args, $options = ['foo' => 'bar'])
    {
        $this->io()->text("Not implemented yet.");

        // The site alias manager has not been added to the DI container yet.
        if (!$this->hasSiteAliasManager()) {
            throw new \Exception('DI container has not been provided the alias manager yet. Implement me!');
        }

        // In theory this might do something once we get an alias manager.
        $siteAlias = $this->siteAliasManager()->get($aliasName);
        $process = new \SiteProcess\SiteProcess($siteAlias, $args);
        $process->mustRun();
    }
}
