<?php
namespace tests;

use Germania\OrderDispatcher\SkuQtyItemValidator;
use Germania\OrderDispatcher\ValidatorInterface;
use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;


class SkuQtyItemValidatorTest  extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;


    public function testSimple()
    {
        $input = array('sku' => "foobar", "quantity" => 100);
        $sut = new SkuQtyItemValidator;
        $this->assertInstanceOf(ValidatorInterface::Class, $sut);

        $result = $sut->validate($input);

        $this->assertIsArray($result);

    }


    /**
     * @dataProvider provideInvalidInput
     * @depends testSimple
     */
    public function testExceptions( $input, $expected_exception, $sut)
    {
        $sut = new SkuQtyItemValidator;

        $this->expectException($expected_exception);
        $sut->validate($input);

    }

    public function provideInvalidInput()
    {
        return array(
            [ array('sku' => null),                       ItemInvalidUserDataException::class ],
            [ array('sku' => ''),                         ItemInvalidUserDataException::class ],
            [ array('sku' => 'foo', 'quantity' => false), ItemInvalidUserDataException::class ],
            [ array('sku' => 'foo', 'quantity' => 'bar'), ItemInvalidUserDataException::class ],

            [ array('sku' => 'foo', 'quantity' => 0),     ItemNotOrderedException::class ],
        );
    }

}
