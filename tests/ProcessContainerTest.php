<?php

namespace roboapp\processqueue;

use Symfony\Component\Process\Process;

class ProcessContainerTest extends \PHPUnit_Framework_TestCase
{

    public function testProcessContainer()
    {
        $process = new Process("sleep 1");
        $callback = function () {
        };


        $class = new ProcessContainer($process, $callback);
        $this->assertEquals($process, $class->getProcess());
        $this->assertEquals($callback, $class->getCallback());
    }

}
