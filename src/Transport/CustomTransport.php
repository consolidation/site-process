<?php

namespace Consolidation\SiteProcess\Transport;

use Consolidation\SiteProcess\SiteProcess;
use Consolidation\SiteProcess\Util\Escape;
use Consolidation\SiteAlias\SiteAliasInterface;
use Consolidation\SiteProcess\Util\Shell;
use Consolidation\Config\ConfigInterface;

/**
 * CustomTransport knows how to wrap a command such that it runs within
 * any cli.
 */
class CustomTransport implements TransportInterface
{
    protected $tty;
    protected $siteAlias;

    public function __construct(SiteAliasInterface $siteAlias)
    {
        $this->siteAlias = $siteAlias;
    }

    /**
     * @inheritdoc
     */
    public function configure(SiteProcess $process)
    {
        $this->tty = $process->isTty();
    }

    /**
     * inheritdoc
     */
    public function wrap($args)
    {
        $cmd = $this->siteAlias->get('custom.command', '');
        $transport = $cmd ? [Shell::preEscaped($cmd)] : [];
        $commandToExecute = $this->getCommandToExecute($args);

        return array_filter(array_merge(
            $transport,
            $commandToExecute
        ));
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd_remote, $args)
    {
        // Make no assumptions about the CLI and what it can support.
        // The CLI itself should handle this with the options specified
        // in the custom command.
        return [];
    }

    /**
     * getCommandToExecute processes the arguments for the command to
     * be executed such that they are appropriate for the transport mechanism.
     */
    protected function getCommandToExecute($args)
    {
        // Escape each argument for the target system and then join
        $args = Escape::argsForSite($this->siteAlias, $args);
        $commandToExecute = implode(' ', $args);

        return [$commandToExecute];
    }
}
