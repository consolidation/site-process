<?php

namespace Consolidation\SiteProcess;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;
use Consolidation\SiteProcess\Util\RealtimeOutputHandler;
use Consolidation\SiteProcess\Util\Escape;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * A wrapper around Symfony Process.
 *
 * - Supports simulated mode. Typically enabled via a --simulate option.
 * - Supports verbose mode - logs all runs.
 * - Can convert output json data into php array (convenience method)
 * - Provides a "realtime output" helper
 */
class ProcessBase extends Process
{
    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * @var OutputInterface
     */
    protected $stderr;

    private $simulated = false;

    private $verbose = false;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Symfony 4 style constructor for creating Process instances from strings.
     * @param string $command The commandline string to run
     * @param string|null $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @return Process
     */
    public static function fromShellCommandline($command, $cwd = null, array $env = null, $input = null, $timeout = 60)
    {
        if (method_exists('\Symfony\Component\Process\Process', 'fromShellCommandline')) {
            return Process::fromShellCommandline($command, $cwd, $env, $input, $timeout);
        }
        return new self($command, $cwd, $env, $input, $timeout);
    }

    /**
     * realtimeStdout returns the output stream that realtime output
     * should be sent to (if applicable)
     *
     * @return OutputStyle $output
     */
    public function realtimeStdout()
    {
        return $this->output;
    }

    protected function realtimeStderr()
    {
        if ($this->stderr) {
            return $this->stderr;
        }
        if (method_exists($this->output, 'getErrorStyle')) {
            return $this->output->getErrorStyle();
        }

        return $this->realtimeStdout();
    }

    /**
     * setRealtimeOutput allows the caller to inject an OutputStyle object
     * that will be used to stream realtime output if applicable.
     *
     * @param OutputStyle $output
     */
    public function setRealtimeOutput(OutputInterface $output, $stderr = null)
    {
        $this->output = $output;
        $this->stderr = $stderr instanceof ConsoleOutputInterface ? $stderr->getErrorOutput() : $stderr;
    }

    /**
     * @return bool
     */
    public function isVerbose()
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
    public function isSimulated()
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
    public function start(callable $callback = null, $env = array())
    {
        $cmd = $this->getCommandLine();
        if ($this->isSimulated()) {
            $this->getLogger()->notice('Simulating: ' . $cmd);
            // Run a command that always succeeds (on Linux and Windows).
            $this->setCommandLine('true');
        } elseif ($this->isVerbose()) {
            $this->getLogger()->info('Executing: ' . $cmd);
        }
        parent::start($callback, $env);
        // Set command back to original value in case anyone asks.
        if ($this->isSimulated()) {
            $this->setCommandLine($cmd);
        }
    }

    /**
     * Get Process output and decode its JSON.
     *
     * @return array
     *   An associative array.
     */
    public function getOutputAsJson()
    {
        $output = trim($this->getOutput());
        if (empty($output)) {
            throw new \InvalidArgumentException('Output is empty.');
        }
        if (Escape::isWindows()) {
            // Doubled double quotes were converted to \\".
            // Revert to double quote.
            $output = str_replace('\\"', '"', $output);
            // Revert of doubled backslashes.
            $output = preg_replace('#\\\\{2}#', '\\', $output);
        }
        $output = $this->removeNonJsonJunk($output);
        $json = json_decode($output, true);
        if (!isset($json)) {
            throw new \InvalidArgumentException('Unable to decode output into JSON.');
        }
        return $json;
    }

    /**
     * Allow for a certain amount of resiliancy in the output received when
     * json is expected.
     *
     * @param string $data
     * @return string
     */
    protected function removeNonJsonJunk($data)
    {
        // Exit early if we have no output.
        $data = trim($data);
        if (empty($data)) {
            return $data;
        }
        // If the data is a simple quoted string, or an array, then exit.
        if ((($data[0] == '"') && ($data[strlen($data) - 1] == '"')) ||
            (($data[0] == "[") && ($data[strlen($data) - 1] == "]"))
        ) {
            return $data;
        }
        // If the json is not a simple string or a simple array, then is must
        // be an associative array. We will remove non-json garbage characters
        // before and after the enclosing curley-braces.
        $start = strpos($data, '{');
        $end = strrpos($data, '}') + 1;
        $data = substr($data, $start, $end - $start);
        return $data;
    }

    /**
     * Return a realTime output object.
     *
     * @return callable
     */
    public function showRealtime()
    {
        $realTimeOutput = new RealtimeOutputHandler($this->realtimeStdout(), $this->realtimeStderr());
        $realTimeOutput->configure($this);
        return $realTimeOutput;
    }
}
