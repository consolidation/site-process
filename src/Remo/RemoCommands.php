<?php

namespace Consolidation\SiteProcess\Remo;

class RemoCommands extends \Robo\Tasks
{
    /**
     * Run a command identified by a site alias
     *
     * @command run
     */
    public function run($siteAlias, array $args, $options = ['foo' => 'bar'])
    {
        $process = new \SiteProcess\SiteProcess($siteAlias, $args);

        $this->io()->text("implementation tbd");
    }
}
