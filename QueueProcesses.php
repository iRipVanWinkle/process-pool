<?php

namespace roboapp\queueprocesses;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class QueueProcesses
{
    /**
     * @var string
     */
    public $commandName;

    /**
     * Limit process is run
     * @var int
     */
    public $limit = 10;

    /**
     * @var string|boolean the functions used to unserialize process data returned. Defaults to false, meaning
     * the data will be retrieved without any deserialization. If you want to use some more efficient
     * serializer (e.g. [igbinary](http://pecl.php.net/package/igbinary)), you may configure this property with
     * a string the deserialization function. If this property is set false, data will be retrieved without
     * any deserialization.
     */
    public $deserializer = false;

    /**
     * @var string|null the working directory or null to use the working dir of the current PHP process
     */
    private $_cwd = null;

    /**
     * @var array|null  the environment variables or null to use the same environment as the current PHP process
     */
    private $_env = null;

    /**
     * @var string|null the input
     */
    private $_input = null;

    /**
     * @var int|float|null the timeout in seconds or null to disable
     */
    private $_timeout = 60;

    /**
     * @var array an array of options for proc_open
     */
    private $_options = [];


    /***
     * @var array
     */
    private $_pool = [];

    /**
     * Processes completed
     * @var int
     */
    private $_completed = 0;

    /**
     * Amount running processes
     * @var int
     */
    private $_running = 0;

    /**
     * @var array
     */
    private $_errors = [];

    /**
     * Length pool
     * @var int
     */
    private $_length = 0;

    /**
     * Constructor.
     *
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @param array|null $env The environment variables or null to use the same environment as the current PHP process
     * @param string|null $input The input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param array $options An array of options for proc_open
     *
     * @throws RuntimeException When proc_open is not installed
     */
    public function __construct($cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array())
    {
        $this->_cwd = $cwd;
        $this->_env = $env;
        $this->_input = $input;
        $this->_timeout = $timeout;
        $this->_options = $options;
    }

    /**
     * Run all processes
     */
    public function run()
    {
        $this->start()->wait();
    }

    /**
     * Wait when all process is complete
     */
    public function wait()
    {
        /** @var ProcessContainer $container */
        foreach ($this->_pool as $container) {
            while ($container->getProcess()->isRunning()) {
                usleep(1000);
            }
        }
    }

    /**
     * Start all process
     * @return $this
     */
    public function start()
    {
        /** @var ProcessContainer $container */
        foreach ($this->_pool as $container) {
            if (!$container->getProcess()->isRunning() && $container->getProcess()->getStatus() !== Process::STATUS_TERMINATED) {
                $container->getProcess()->start($container->getCallback());
                $this->_running++;
            }

            if ($this->_running === $this->limit) break;
        }

        return $this;
    }

    /**
     * Check running
     * @return bool
     */
    public function isRunning()
    {
        $isRunning = false;

        /** @var ProcessContainer $container */
        foreach ($this->_pool as $container) {
            if ($container->getProcess()->isRunning()) {
                $isRunning = true;
            }

        }

        return $isRunning;
    }

    /**
     * Clear pool
     * @throws \ErrorException
     */
    public function clear()
    {
        if ($this->isRunning()) {
            throw new \ErrorException("One or more process is running.");
        }

        $this->_pool = [];
        $this->_length = 0;
    }

    /**
     * @param $command
     * @param null $callback
     */
    public
    function addCommand($command, $callback = null)
    {
        $this->_pool[] = new ProcessContainer(
            $this->_buildProcess($command),
            $this->_buildCallback($callback)
        );

        $this->_length++;
    }

    /**
     * @param $command
     * @return Process
     */
    private
    function _buildProcess($command)
    {
        return new Process($command, $this->_cwd, $this->_env, $this->_input, $this->_timeout, $this->_options);
    }

    /**
     * @param Closure $callback
     * @return Closure
     */
    private
    function _buildCallback($callback)
    {
        $self = $this;
        $out = Process::OUT;

        $callback = function ($type, $data) use ($self, $callback, $out) {
            if ($out == $type) {
                if ($this->deserializer) {
                    $data = call_user_func($this->deserializer, $data);
                }
            } else {
                $self->addError($data);
            }

            $self->processCompleted();

            if (null !== $callback) {
                call_user_func($callback, $type, $data);
            }
        };

        return $callback;
    }

    /**
     * Add error
     * @param $error
     */
    protected
    function addError($error)
    {
        $this->_errors[] = $error;
    }

    public
    function processCompleted()
    {
        $this->_completed++;
        $this->_running--;

        if ($this->_length > $this->_completed + $this->_running) {
            $this->start();
        }
    }

    /**
     * Amount processes in pool
     * @return int
     */
    public
    function getLength()
    {
        return $this->_length;
    }

    /**
     * @param $deserializer
     */
    public
    function setDeserializer($deserializer)
    {
        $this->deserializer = $deserializer;
    }

    /**
     * @param $limit
     */
    public
    function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param Process $process
     * @param null $callback
     */
    public
    function addProcess(Process $process, $callback = null)
    {
        $this->_pool[] = new ProcessContainer(
            $process,
            $this->_buildCallback($callback)
        );

        $this->_length++;
    }

    /**
     * Error list
     * @return array
     */
    public
    function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Amount processes completed
     * @return int
     */
    public
    function getCompleted()
    {
        return $this->_completed;
    }

    /**
     * Amount processes running
     * @return int
     */
    public
    function getRunning()
    {
        return $this->_running;
    }
}
