<?php

namespace rollun\callback\PidKiller;

use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Promise\Interfaces\PayloadInterface;

class WorkerProducer extends QueueFiller {}
