<?php
namespace tests;

use Germania\OrderDispatcher\ContainerItemFactory;
use Germania\OrderDispatcher\ItemFactoryInterface;
use Germania\OrderDispatcher\Exceptions\ItemNotAvailableException;
use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;
use Germania\OrderDispatcher\ValidatorItemFactoryDecorator;
use Germania\OrderDispatcher\SkuQtyItemValidator;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PsrContainer;

class ContainerItemFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public $logger;


    public function setUp() : void
    {
        parent::setUp();

        $filter = new \Laminas\Log\Filter\Priority( \Laminas\Log\Logger::DEBUG );

        $writer = new \Laminas\Log\Writer\Stream('php://output');
        $writer->addFilter($filter);

        $laminasLogLogger = new \Laminas\Log\Logger;
        $laminasLogLogger->addWriter($writer);

        $this->logger = new \Laminas\Log\PsrLoggerAdapter($laminasLogLogger);
    }



    public function testInstantiation()
    {
        $ac_stub = $this->prophesize( ContainerInterface::class );
        $ac = $ac_stub->reveal();

        $sut = new ContainerItemFactory( $ac, "sku", $this->logger );
        $this->assertInstanceOf( ItemFactoryInterface::class, $sut );

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testFactoryMethodWithAvailableItem( $sut )
    {
        $sku = "foobar";
        $qty = 50;

        $found_article = [
            'foo' => 'bar',
            'maxQuantity' => 100
        ];

        $ac_stub = $this->prophesize( ContainerInterface::class );
        $ac_stub->get( Argument::exact( $sku ) )->willReturn($found_article);
        $ac = $ac_stub->reveal();

        $sut->setArticlesContainer( $ac );

        $user_item_data = [
            'sku' => $sku,
            'quantity' => $qty,
        ];

        $order_item = $sut->createItem($user_item_data);
        $this->assertEquals( $order_item['foo'], 'bar' );
    }




    /**
     * @dataProvider provideInvalidUserData
     * @depends testInstantiation
     */
    public function testFactoryMethodWithInvalidUserData( $invalid_data, $expected_exception, $sut )
    {


        $ac_stub = $this->prophesize( ContainerInterface::class );
        $ac = $ac_stub->reveal();

        $sut->setArticlesContainer( $ac );

        $sut = new ValidatorItemFactoryDecorator($sut, new SkuQtyItemValidator() );


        $this->expectException( $expected_exception );
        $sut->createItem($invalid_data);
    }


    public function provideInvalidUserData()
    {
        return array(
            [ array(), ItemInvalidUserDataException::class ],
            [ array('sku' => ""), ItemInvalidUserDataException::class ],
            [ array('sku' => false), ItemInvalidUserDataException::class ],
            [ array('sku' => null), ItemInvalidUserDataException::class ],
            [ array('sku' => "foo", "quantity" => false), ItemInvalidUserDataException::class ],
            [ array('sku' => "foo", "quantity" => -2), ItemInvalidUserDataException::class ],
            [ array('sku' => "foo", "quantity" => "bar"), ItemInvalidUserDataException::class ],
            [ array('sku' => "foo", "quantity" => 0), ItemNotOrderedException::class ],
        );
    }




    /**
     * @depends testInstantiation
     */
    public function testFactoryMethodWithUnavailableItem( $sut )
    {
        $empty_container = new PsrContainer(new PimpleContainer(array()));

        $sut->setArticlesContainer( $empty_container );

        $user_item_data = [
            'sku' => "foo",
            'quantity' => 100
        ];

        $this->expectException(ItemNotAvailableException::class);
        $sut->createItem($user_item_data);
    }
}
