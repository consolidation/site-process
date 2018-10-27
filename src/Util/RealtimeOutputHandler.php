<?php
namespace Consolidation\SiteProcess\Util;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;
use Consolidation\Config\Util\Interpolator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * RealtimeOutput can be provided to a process object when you want
 * to display the output of the running command as it is being produced.
 */
class RealtimeOutputHandler
{
    protected $stdout;
    protected $stderr;

    /**
     * Provide the output streams to use for stdout and stderr
     */
    public function __construct(OutputInterface $stdout, OutputInterface $stderr)
    {
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * This gives us an opportunity to adapt to the settings of the
     * process object (e.g. do we need to do anything differently if
     * it is in tty mode, etc.)
     */
    public function configure(Process $process)
    {
    }

    /**
     * Helper method when you want real-time output from a Process call.
     * @param string $type
     * @param string $buffer
     */
    public function handleOutput($type, $buffer)
    {
        if (Process::ERR === $type) {
            $this->stderr->write('ERR > ' . $buffer);
        } else {
            $this->stdout->write($buffer);
        }
    }
}
