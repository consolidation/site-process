<?php

namespace Consolidation\SiteProcess\Transport;

use Drush\Drush;
use Psr\Log\LoggerInterface;
use Robo\Common\IO;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;
use Consolidation\SiteProcess\Util\RealtimeOutputHandler;
use Consolidation\SiteProcess\Util\Escape;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Util\Shell;

/**
 * SshTransport knows how to wrap a command such that it runs on a remote
 * system via the ssh cli.
 */
class SshTransport implements TransportInterface
{
    protected $tty;
    protected $siteAlias;

    public function __construct(AliasRecord $siteAlias)
    {
        $this->siteAlias = $siteAlias;
    }

    /**
     * inheritdoc
     */
    public function configure(Process $process)
    {
        $this->tty = $process->isTty();
    }

    /**
     * inheritdoc
     */
    public function wrap($args)
    {
        $transport = ['ssh'];
        $transportOptions = $this->getTransportOptions();
        $commandToExecute = $this->getCommandToExecute($args);

        return array_merge(
            $transport,
            $transportOptions,
            $commandToExecute
        );
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd, $args)
    {
        return array_merge(
            [
                'cd',
                $cd,
                Shell::op('&&'),
            ],
            $args
        );
    }

    /**
     * getTransportOptions returns the transport options for the tranport
     * mechanism itself
     */
    protected function getTransportOptions()
    {
        $transportOptions = [
            Shell::preEscaped($this->siteAlias->get('ssh.options', '-o PasswordAuthentication=no')),
            $this->siteAlias->remoteHostWithUser(),
        ];
        if ($this->tty) {
            array_unshift($transportOptions, '-t');
        }
        return $transportOptions;
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
