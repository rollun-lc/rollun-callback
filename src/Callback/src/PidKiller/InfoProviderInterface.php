<?php

namespace rollun\callback\PidKiller;

interface InfoProviderInterface
{
    public function getInfo(): string;
}
