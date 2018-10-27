<?php

namespace Consolidation\SiteProcess;

use Drush\Drush;
use Psr\Log\LoggerInterface;
use Robo\Common\IO;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;
use Consolidation\SiteProcess\Util\RealtimeOutput;

/**
 * A wrapper around Symfony Process.
 *
 * - Supports simulated mode. Typically enabled via a --simulate option.
 * - Supports verbose mode - logs all runs.
 */
class ProcessBase extends Process
{
    /**
     * @var OutputStyle
     */
    protected $output;

    private $simulated = false;

    private $verbose = false;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * realtimeOutput returns the output stream that realtime output
     * should be sent to (if applicable)
     *
     * @return OutputStyle $output
     */
    public function realtimeOutput()
    {
        return $this->output;
    }

    /**
     * setRealtimeOutput allows the caller to inject an OutputStyle object
     * that will be used to stream realtime output if applicable.
     *
     * @param OutputStyle $output
     */
    public function setRealtimeOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return bool
     */
    public function getVerbose()
    {
        return $this->verbose;
    }

    /**
     * @param bool $verbose
     */
    public function setVerbose($verbose)
    {
        $this->verbose = $verbose;
    }

    /**
     * @return bool
     */
    public function getSimulated()
    {
        return $this->simulated;
    }

    /**
     * @param bool $simulated
     */
    public function setSimulated($simulated)
    {
        $this->simulated = $simulated;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function start(callable $callback = null)
    {
        $cmd = $this->getCommandLine();
        if ($this->getSimulated()) {
            $this->getLogger()->notice('Simulating: ' . $cmd);
            // Run a command that always succeeds.
            $this->setCommandLine('exit 0');
        } elseif ($this->getVerbose()) {
            $this->getLogger()->info('Executing: ' . $cmd);
        }
        parent::start($callback);
        // Set command back to original value in case anyone asks.
        if ($this->getSimulated()) {
            $this->setCommandLine($cmd);
        }
    }

    /**
     * Return a realTime output object.
     *
     * @return callable
     */
    public function showRealtime()
    {
        $realTimeOutput = new RealtimeOutputHandler($this->realtimeOutput(), $this->realtimeOutput()->getErrorOutput());
        $realTimeOutput->configure($this);
        return [$realTimeOutput, 'handleOutput'];
    }
}
