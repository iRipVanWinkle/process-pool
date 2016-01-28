<?php

namespace roboapp\processpool;


class ProcessContainer
{
    /**
     * @var \Symfony\Component\Process\Process
     */
    private $_process;

    /**
     * @var callable
     */
    private $_callback;

    /**
     * @param \Symfony\Component\Process\Process $process
     * @param \Closure $callback
     */
    public function __construct($process, $callback)
    {
        $this->_process = $process;
        $this->_callback = $callback;
    }

    /**
     * @return \Symfony\Component\Process\Process
     */
    public function getProcess()
    {
        return $this->_process;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->_callback;
    }
}