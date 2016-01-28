<?php

namespace roboapp\processpool;


use Symfony\Component\Process\PhpExecutableFinder;

class ProcessPoolTest extends \PHPUnit_Framework_TestCase
{

    private static $phpBin;

    public static function setUpBeforeClass()
    {
        $phpBin = new PhpExecutableFinder();
        self::$phpBin = 'phpdbg' === PHP_SAPI ? 'php' : $phpBin->find();
        if ('\\' !== DIRECTORY_SEPARATOR) {
            // exec is mandatory to deal with sending a signal to the process
            // see https://github.com/symfony/symfony/issues/5030 about prepending
            // command with exec
            self::$phpBin = 'exec ' . self::$phpBin;
        }
    }

    public function testDisableProcOpn()
    {
        ini_set('disable_functions', 'proc_open');

        $pool = new ProcessPool();
    }

    public function testProcessContainer()
    {
        $pool = new ProcessPool();
        $pool->addCommand('sleep 1');
        $pool->run();
    }

    public function testProcessCallback()
    {
        $pool = new ProcessPool();
        $pool->addCommand('echo foo', function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });
        $pool->addCommand('echo bar', function ($type, $buffer) use (&$data) {
            $data .= $buffer;
        });

        $pool->run();

        $this->assertSame('foo' . PHP_EOL . 'bar' . PHP_EOL, $data);
    }

    public function testPoolInfo()
    {
        $pool = new ProcessPool();
        $pool->addCommand('sleep 1');
        $pool->addCommand('sleep 2');
        $pool->addCommand('sleep 3');

        $this->assertEquals(3, $pool->getLength());
        $this->assertEquals(0, $pool->getCompleted());
        $this->assertEquals(0, $pool->getRunning());

        $pool->start();

        $this->assertEquals(3, $pool->getRunning());

        sleep(1);

        //$this->assertEquals(1, $pool->getCompleted());

    }
}
