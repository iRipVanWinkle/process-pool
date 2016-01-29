<?php

namespace roboapp\processqueue;

use Symfony\Component\Process\PhpProcess;

class ProcessQueueTest extends \PHPUnit_Framework_TestCase
{

    public function testPoolInfo()
    {
        $queue = new ProcessQueue();
        $queue->addCommand('echo foo');
        $queue->addCommand('echo bar');

        $queue->setLimit(1);

        $this->assertEquals(2, $queue->getLength());
        $this->assertEquals(0, $queue->getCompleted());
        $this->assertEquals(0, $queue->getRunning());
        $this->assertFalse($queue->isRunning());

        $queue->start();

        $this->assertEquals(1, $queue->getRunning());
        $this->assertEquals(0, $queue->getCompleted());
        $this->assertTrue($queue->isRunning());

        $queue->wait();

        $this->assertEquals(2, $queue->getCompleted());
        $this->assertFalse($queue->isRunning());

    }

    public function testProcessClear()
    {
        $queue = new ProcessQueue();
        $queue->addProcess(new PhpProcess('<?= "foo" ?>'));
        $this->assertEquals(1, $queue->getLength());
        $queue->clear();
        $this->assertEquals(0, $queue->getLength());
    }

    /**
     * @expectedException \ErrorException
     */
    public function testProcessClearException()
    {
        $queue = new ProcessQueue();
        $queue->addCommand('echo foo');
        $queue->start();

        $queue->clear();
    }

    public function testProcessError()
    {
        $queue = new ProcessQueue();
        $queue->addProcess(new PhpProcess('<?= "No exception"; ?>'));
        $queue->addProcess(new PhpProcess('<?php throw Exception("Exception"); ?>'));

        $this->assertEquals([], $queue->getErrors());

        $queue->run();

        $this->assertEquals(1, count($queue->getErrors()));
    }

    public function testProcessUseDeserializer()
    {
        $queue = new ProcessQueue();
        $queue->setDeserializer('unserialize');

        $queue->addProcess(new PhpProcess("<?= serialize('foo'); ?>"), function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });
        $queue->addProcess(new PhpProcess("<?= serialize('bar');  ?>"), function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });

        $queue->run();

        $this->assertSame('foobar', $data);
    }

    public function testProcessCallback()
    {
        $queue = new ProcessQueue();
        $queue->addCommand('echo foo', function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });
        $queue->addCommand('echo bar', function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });

        $queue->run();

        $this->assertSame('foo' . PHP_EOL . 'bar' . PHP_EOL, $data);
    }
}
