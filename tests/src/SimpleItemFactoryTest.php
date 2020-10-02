<?php
namespace tests;

use Germania\OrderDispatcher\SimpleItemFactory;
use Germania\OrderDispatcher\ItemFactoryInterface;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;


class SimpleItemFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait, LoggerTrait;

    public $logger;


    public function setUp() : void
    {
        parent::setUp();

        $this->logger = $this->getLogger();
    }



    public function testInstantiation()
    {
        $sut = new SimpleItemFactory( $this->logger );
        $this->assertInstanceOf( ItemFactoryInterface::class, $sut );

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testFactoryMethod( $sut )
    {
        $sku = "foobar";
        $qty = 50;

        $user_item_data = [
            'sku' => $sku,
            'quantity' => $qty,
            'foo' => 'bar'

        ];
        $order_item = $sut->createItem($user_item_data);

        $this->assertArrayHasKey( "sku", $order_item);
        $this->assertEquals( $order_item['quantity'], $qty );
        $this->assertEquals( $order_item['foo'], 'bar' );
    }



}
