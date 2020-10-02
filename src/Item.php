<?php
namespace Germania\OrderDispatcher;


class Item extends \ArrayObject implements ItemInterface
{
    public function jsonSerialize()
    {
        $data = $this->getArrayCopy();
        return $data;
    }
}
