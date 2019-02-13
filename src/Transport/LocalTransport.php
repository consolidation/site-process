<?php

namespace Consolidation\SiteProcess\Transport;

use Symfony\Component\Process\Process;

/**
 * LocalTransport just runs the command on the local system.
 */
class LocalTransport implements TransportInterface
{
    protected $process;

    /**
     * @inheritdoc
     */
    public function configure(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function wrap($args)
    {
        return $args;
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd, $args)
    {
        $this->process->setWorkingDirectory($cd);
        return $args;
    }
}
