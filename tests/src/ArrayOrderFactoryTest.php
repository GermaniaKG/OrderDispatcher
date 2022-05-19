<?php
namespace tests;

use Germania\OrderDispatcher\ContainerItemFactory;
use Germania\OrderDispatcher\ArrayOrderFactory;
use Germania\OrderDispatcher\OrderFactoryInterface;
use Germania\OrderDispatcher\OrderInterface;
use Germania\OrderDispatcher\ItemFactoryInterface;
use Germania\OrderDispatcher\FilterValidator;

use Germania\OrderDispatcher\Exceptions\OrderFactoryExceptionInterface;
use Germania\OrderDispatcher\Exceptions\NoArticlesOrderedException;
use Germania\OrderDispatcher\Exceptions\RequiredUserDataMissingException;

use Germania\OrderDispatcher\Exceptions\ItemNotAvailableException;
use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PsrContainer;


class ArrayOrderFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait, LoggerTrait;


    /**
     * @var ItemFactoryInterface
     */
    public $item_factory;

    public $default_field_validation = array(
        "email"  =>  FILTER_VALIDATE_EMAIL,
        "company"         =>  FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        "retailer_number" =>  array("filter" => FILTER_VALIDATE_REGEXP, "options" => [
            'regexp'=>"/^[\d\-]+$/"
        ]),
        "privacyAck"      =>  FILTER_VALIDATE_BOOLEAN
    );


    public function setUp() : void
    {
        parent::setUp();

        $this->logger = $this->getLogger();

        $available_articles = array(
            "foo" => ['bar' => "baz"]
        );

        $available_articles_container = new PsrContainer(new PimpleContainer($available_articles));
        $this->item_factory = new ContainerItemFactory($available_articles_container, "sku", $this->logger);
    }


    public function testInstantiation()
    {
        $sut = new ArrayOrderFactory( $this->item_factory, "articles", $this->logger);
        $this->assertInstanceOf( OrderFactoryInterface::class, $sut);

        return $sut;
    }


    /**
     * @depends testInstantiation
     * @dataProvider provideInvalidUserData
     */
    public function testCreateOrderWithInvalidUserData( $invalid_user_data, $sut )
    {
        $this->expectException(OrderFactoryExceptionInterface::class);
        $this->expectException(RequiredUserDataMissingException::class);

        $sut->setValidator( new FilterValidator( $this->default_field_validation) );

        $sut->createOrder( $invalid_user_data );
    }

    public function provideInvalidUserData()
    {
        $valid_user_input = array(
            'email'           => "test@test.com",
            'company'         => "ACME Corp.",
            'retailer_number' => "12345",
            'privacyAck'      => 1
        );

        $no_email           = array_filter(array_merge($valid_user_input, [ 'email' => null ]));
        $wrong_email        = array_filter(array_merge($valid_user_input, [ 'email' => "foo" ]));
        $no_company         = array_filter(array_merge($valid_user_input, [ 'company' => null ]));
        $no_retailer_number = array_filter(array_merge($valid_user_input, [ 'retailer_number' => null ]));
        $no_privacyAck      = array_filter(array_merge($valid_user_input, [ 'privacyAck' => null ]));


        return array(
            [ $no_email ],
            [ $wrong_email ],
            [ $no_company ],
            [ $no_retailer_number ],
            [ $no_privacyAck ],
        );

    }



    /**
     * @depends testInstantiation
     */
    public function testCreateOrderWithValidUserData( $sut )
    {
        $input = array(
            'email' => "test@test.com",
            'company' => "ACME Corp.",
            'retailer_number' => "12345",
            'privacyAck' => 1,
            'articles' => array(
                [ 'sku' => "foo" , "quantity" => 5 ]
            )
        );

        $order = $sut->createOrder( $input );

        $this->assertInstanceOf( OrderInterface::class, $order);
        $customer_data = $order->getCustomerData();

        $this->assertArrayHasKey("email", $customer_data);
        $this->assertArrayHasKey("company", $customer_data);
        $this->assertArrayHasKey("retailer_number", $customer_data);
        $this->assertArrayHasKey("privacyAck", $customer_data);
    }



    /**
     * @depends testInstantiation
     * @dataProvider provideInvalidItemData
     */
    public function testCreateOrderWithValidUserDataButNoItems( $articles, $sut )
    {
        $input = array(
            'email' => "test@test.com",
            'company' => "ACME Corp.",
            'retailer_number' => "12345",
            'privacyAck' => 1,
            'articles' => $articles
        );


        $sut->setValidator( new FilterValidator( $this->default_field_validation) );

        $this->expectException( NoArticlesOrderedException::class );
        $this->expectException( OrderFactoryExceptionInterface::class );

        $sut->createOrder( $input );
    }

    public function provideInvalidItemData()
    {
        return array(
            [ null ],
            [ false ],
            [ array("string") ],
            [ "string" ],
            [ array('sku' => "kilo" , "quantity" => 5) ],
        );
    }


    protected function createLaminasLogger()
    {
        $loglevel = \Laminas\Log\Logger::DEBUG;
        $filter = new \Laminas\Log\Filter\Priority( $loglevel );

        $writer = new \Laminas\Log\Writer\Stream('php://output');
        $writer->addFilter($filter);

        $laminasLogLogger = new \Laminas\Log\Logger;
        $laminasLogLogger->addWriter($writer);

        return new \Laminas\Log\PsrLoggerAdapter($laminasLogLogger);
    }
}
