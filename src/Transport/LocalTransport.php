<?php

namespace Consolidation\SiteProcess\Transport;

use Symfony\Component\Process\Process;

/**
 * LocalTransport just runs the command on the local system.
 */
class LocalTransport implements TransportInterface
{
    /**
     * @inheritdoc
     */
    public function configure(Process $process)
    {
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
        return $args;
    }
}
