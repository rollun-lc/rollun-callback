<?php


namespace rollun\callback\PidKiller;


interface WriterInterface
{
    /**
     * Write data to some.
     * @param $data
     * @return mixed|void
     */
    public function write($data);
}