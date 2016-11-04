# The Queue of Processes
[![Build Status](https://img.shields.io/travis/iRipVanWinkle/queue-processes/master.svg?style=flat-square)](https://travis-ci.org/iRipVanWinkle/queue-processes) [![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/iRipVanWinkle/queue-processes/master/LICENSE) [![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat-square)](http://www.yiiframework.com/)

The queue for symfony/process

## Install

Via Composer

``` bash
$ composer require iripvanwinkle/queue-processes
```
## Usage

``` php
    $queue = new QueueProcesses();
    foreach (range(1, 10) as $index) {
        $queue->addCommand("echo $index", function ($type, $buffer) use (&$commonBuffer) {
            $commonBuffer .= $buffer;
        });
    }
    
    $queue->setLimit(2);
    $queue->run();

    echo $commonBuffer; // Output: 1\n2\n3\n4\n5\n6\n7\n8\n9\n10
```

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
