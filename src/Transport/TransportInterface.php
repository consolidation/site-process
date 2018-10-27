<?php

namespace Consolidation\SiteProcess\Transport;

use Symfony\Component\Process\Process;

/**
 * SshTransport knows how to wrap a command such that it runs on a remote
 * system via the ssh cli.
 */
interface TransportInterface
{
    /**
     * Configure ourselves based on the settings of the process object
     * (e.g. isTty()).
     */
    public function configure(Process $process);

    /**
     * wrapWithTransport examines the provided site alias; if it is a local
     * alias, then the provided arguments are returned unmodified. If the
     * alias points at a remote system, though, then the arguments are
     * escaped and wrapped in an appropriate ssh command.
     *
     * @param AliasRecord $siteAlias alias record of target site.
     * @param array $args arguments provided by caller.
     * @return array command and arguments to execute.
     */
    public function wrap($args);

    /**
     * addChdir adds an appropriate 'chdir' / 'cd' command for the transport.
     */
    public function addChdir($cd, $args);
}
