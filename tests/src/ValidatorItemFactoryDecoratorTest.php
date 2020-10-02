<?php
namespace tests;

use Germania\OrderDispatcher\ValidatorItemFactoryDecorator;
use Germania\OrderDispatcher\ItemFactoryInterface;
use Germania\OrderDispatcher\ItemInterface;
use Germania\OrderDispatcher\FilterValidator;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

class ValidatorItemFactoryDecoratorTest extends  \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;


    public function testInstantiation()
    {
        $item_factory_mock = $this->prophesize( ItemFactoryInterface::class );
        $item_factory = $item_factory_mock->reveal();

        $validator = new FilterValidator(array());

        $sut = new ValidatorItemFactoryDecorator($item_factory, $validator);
        $sut->setValidator($validator);
        $sut->setItemFactory($item_factory);

        $this->assertInstanceOf(ItemFactoryInterface::class, $sut);

        return $sut;

    }


    /**
     * @depends testInstantiation
     */
    public function testFactoryMethod( $sut )
    {
        $item_mock = $this->prophesize(ItemInterface::class);
        $item = $item_mock->reveal();

        $item_factory_mock = $this->prophesize( ItemFactoryInterface::class );
        $item_factory_mock->createItem(Argument::type('array'))->willReturn( $item );
        $item_factory = $item_factory_mock->reveal();

        $sut->setItemFactory($item_factory);

        $validator = new FilterValidator(array());
        $sut->setValidator($validator);

        $input = array('foo' => "bar");
        $result = $sut->createItem( $input );

        $this->assertInstanceOf( ItemInterface::class, $result);

    }


}
