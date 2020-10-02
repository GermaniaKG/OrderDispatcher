<?php
namespace tests;

use Germania\OrderDispatcher\Item;
use Germania\OrderDispatcher\ItemInterface;


class ItemTest extends  \PHPUnit\Framework\TestCase
{
    public function testInstantiation()
    {
        $sut = new Item;
        $this->assertInstanceOf(ItemInterface::class, $sut);
    }
}
