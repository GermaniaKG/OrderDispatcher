<?php
namespace tests;

use Germania\OrderDispatcher\OrderInterface;
use Germania\OrderDispatcher\OrderHandlerController;
use Germania\OrderDispatcher\OrderFactoryInterface;
use Germania\OrderDispatcher\OrderHandlerInterface;

use Germania\OrderDispatcher\Exceptions\RequiredUserDataMissingException;
use Germania\OrderDispatcher\Exceptions\OrderHandlerRuntimeException;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

class OrderHandlerControllerTest extends \PHPUnit\Framework\TestCase
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


    public function mockServerRequest( array $parsed_body ) : ServerRequestInterface
    {
        $request_mock = $this->prophesize(ServerRequestInterface::class);
        $request_mock->getParsedBody()->willReturn( $parsed_body );

        return $request_mock->reveal();
    }


    public function testInstantiation()
    {
        $order_handler_stub = $this->prophesize(OrderHandlerInterface::class);
        $order_handler = $order_handler_stub->reveal();

        $order_factory_stub = $this->prophesize(OrderFactoryInterface::class);
        $order_factory = $order_factory_stub->reveal();


        $sut = new OrderHandlerController($order_factory, $order_handler, $this->logger);

        $this->assertIsCallable( $sut );

        return $sut;
    }


    /**
     * @dataProvider provideValidUserData
     */
    public function testRunValidRequest($request, $response, $expected_status)
    {
        $order_stub = $this->prophesize(OrderInterface::class);
        $order = $order_stub->reveal();

        $order_factory_stub = $this->prophesize(OrderFactoryInterface::class);
        $order_factory_stub->createOrder(Argument::type('array'))->willReturn( $order );
        $order_factory = $order_factory_stub->reveal();

        $order_handler_stub = $this->prophesize(OrderHandlerInterface::class);
        $order_handler_stub->handle(Argument::any())->willReturn( true );
        $order_handler = $order_handler_stub->reveal();

        $sut = new OrderHandlerController($order_factory, $order_handler, $this->logger);

        $result_response = $sut($request, $response);
        $this->assertEquals($expected_status, $result_response->getStatusCode());
    }



    public function provideValidUserData()
    {
        $valid_user_input = array(
            'email'           => "test@test.com",
            'company'         => "ACME Corp.",
            'retailer_number' => "12345",
            'privacyAck'      => 1
        );

        $response = (new Psr17Factory)->createResponse(200);

        return array(
            [ $this->mockServerRequest( $valid_user_input ), $response, 200 ]
        );

    }



    /**
     * @dataProvider provideInvalidUserData
     */
    public function testRunInvalidRequestWithFactoryException( $request, $response, $factory_exception, $expected_status )
    {
        $order_factory_stub = $this->prophesize(OrderFactoryInterface::class);
        $order_factory_stub->createOrder(Argument::type('array'))->willThrow( $factory_exception );
        $order_factory = $order_factory_stub->reveal();

        $order_handler_stub = $this->prophesize(OrderHandlerInterface::class);
        $order_handler = $order_handler_stub->reveal();

        $sut = new OrderHandlerController($order_factory, $order_handler, $this->logger);
        $sut->setResponseHeaderName("TestResult");

        $result_response = $sut($request, $response);

        $this->assertEquals($expected_status, $result_response->getStatusCode());

        $result_body = $result_response->getBody()->__toString();
        $result_decoded = json_decode($result_body, "ForceArray");
        $this->assertArrayHasKey('errors', $result_decoded);
        $this->assertEquals($result_response->getHeaderLine("TestResult"), $factory_exception);

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


        $response = (new Psr17Factory)->createResponse(200);

        return array(
            [ $this->mockServerRequest( $no_email ),           $response, RequiredUserDataMissingException::class, 400 ],
            [ $this->mockServerRequest( $wrong_email ),        $response, RequiredUserDataMissingException::class, 400 ],
            [ $this->mockServerRequest( $no_company ),         $response, RequiredUserDataMissingException::class, 400 ],
            [ $this->mockServerRequest( $no_retailer_number ), $response, RequiredUserDataMissingException::class, 400 ],
            [ $this->mockServerRequest( $no_privacyAck ),      $response, RequiredUserDataMissingException::class, 400 ],
        );

    }



    /**
     * @dataProvider provideInvalidUserDataWithInvalidItemData
     */
    public function testRunInvalidRequestWithDispatcherException( $request, $response, $dispatcher_exception, $expected_status )
    {
        $order_stub = $this->prophesize(OrderInterface::class);
        $order = $order_stub->reveal();

        $order_factory_stub = $this->prophesize(OrderFactoryInterface::class);
        $order_factory_stub->createOrder(Argument::type('array'))->willReturn( $order );
        $order_factory = $order_factory_stub->reveal();

        $order_handler_stub = $this->prophesize(OrderHandlerInterface::class);
        $order_handler_stub->handle(Argument::any())->willThrow( $dispatcher_exception );
        $order_handler = $order_handler_stub->reveal();


        $sut = new OrderHandlerController($order_factory, $order_handler, $this->logger);
        $sut->setResponseHeaderName("TestResult");

        $result_response = $sut($request, $response);

        $this->assertEquals($expected_status, $result_response->getStatusCode());
        $this->assertEquals($result_response->getHeaderLine("TestResult"), $dispatcher_exception);

    }


    public function provideInvalidUserDataWithInvalidItemData()
    {
        $valid_user_input = array(
            'email'           => "test@test.com",
            'company'         => "ACME Corp.",
            'retailer_number' => "12345",
            'privacyAck'      => 1
        );

        $response = (new Psr17Factory)->createResponse(200);

        return array(
            [ $this->mockServerRequest( $valid_user_input ), $response, OrderHandlerRuntimeException::class, 500 ],
            [ $this->mockServerRequest( $valid_user_input ), $response, \Exception::class, 500 ]
        );

    }

}
