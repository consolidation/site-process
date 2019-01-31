<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Consolidation\SiteProcess\Util\ArgumentProcessor;
use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;

class RealtimeOutputHandlerTest extends TestCase
{
    /**
     * Data provider for testRealtimeOutputHandler.
     */
    public function realtimeOutputHandlerTestValues()
    {
        return [
            [
                'hello, world',
                '',
                ['echo', 'hello, world'],
            ],

            [
                'README.md',
                '',
                ['ls', 'README.md'],
            ],

            [
                '',
                'no/such/file: No such file or directory',
                ['ls', 'no/such/file'],
            ],
        ];
    }

    /**
     * Test the RealtimeOutputHandler class.
     *
     * @dataProvider realtimeOutputHandlerTestValues
     */
    public function testRealtimeOutputHandler($expectedStdout, $expectedStderr, $args)
    {
        $stdin = new ArrayInput([]);
        $stdout = new BufferedOutput();
        $stderr = new BufferedOutput();
        $symfonyStyle = new SymfonyStyle($stdin, $stdout);

        $process = new ProcessBase($args);
        $process->setRealtimeOutput($symfonyStyle, $stderr);
        $process->run($process->showRealtime());

        $this->assertEquals($expectedStdout, trim($stdout->fetch()));
        if (empty($expectedStderr)) {
            $this->assertEquals('', trim($stderr->fetch()));
        }
        else {
            $this->assertContains($expectedStderr, trim($stderr->fetch()));
        }
    }
}
