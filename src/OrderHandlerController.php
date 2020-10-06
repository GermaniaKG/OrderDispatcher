<?php
namespace Germania\OrderDispatcher;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Nyholm\Psr7\Factory\Psr17Factory;

use Germania\OrderDispatcher\Exceptions\OrderFactoryExceptionInterface;

class OrderHandlerController
{
    use LoggerAwareTrait;

    /**
     * @var OrderHandlerInterface
     */
    public $order_handler;


    /**
     * @var OrderFactoryInterface
     */
    public $order_factory;


    public $debug = false;


    /**
     * Response "error type" header name
     * @var string
     */
    public $response_header_name = "X-Order-Dispatch-Message";


    /**
     * @var ResponderInterface
     */
    public $responder;



    /**
     * @param OrderFactoryInterface   $order_factory    Order factory
     * @param OrderHandlerInterface   $order_handler    Order handler
     * @param LoggerInterface|null    $logger           Optional: PSR-3 Logger
     */
    public function __construct( OrderFactoryInterface $order_factory, OrderHandlerInterface $order_handler, LoggerInterface $logger = null )
    {
        $this->setOrderFactory($order_factory);
        $this->setOrderHandler($order_handler);
        $this->setLogger( $logger ?: new NullLogger );

        $this->responder = new JsonResponder( new Psr17Factory, null, $this->debug );
    }


    /**
     * @param string $response_header_name Response header name
     */
    public function setResponseHeaderName( string $response_header_name)
    {
        $this->response_header_name = $response_header_name;
        return $this;
    }


    /**
     * Returns the response header name.
     *
     * @return string
     */
    public function getResponseHeaderName() : string
    {
        return $this->response_header_name;
    }


    /**
     * Sets the Order factory.
     *
     * @param OrderFactoryInterface $order_factory
     */
    public function setOrderFactory( OrderFactoryInterface $order_factory)
    {
        $this->order_factory = $order_factory;
        return $this;
    }



    /**
     * Sets the Order handler.
     *
     * @param OrderHandlerInterface $order_handler
     */
    public function setOrderHandler( OrderHandlerInterface $order_handler)
    {
        $this->order_handler = $order_handler;
        return $this;
    }



    /**
     * Creates an OrderInterface instance from Request body
     * and handles it using the OrderHandlerInterface.
     *
     * In case of Exceptions (user data missing or dispatching failed)
     * the response will have an "X-Order-Dispatch-Message" with the class name
     * of the exception.
     *
     * @param  ServerRequestInterface $request  Request
     * @param  ResponseInterface      $response Response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {

        try {
            $input = $request->getParsedBody();
            $order = $this->order_factory->createOrder( $input );
            $this->order_handler->handle( $order);
        }
        catch (OrderFactoryExceptionInterface $e) {
            return $this->createErrorResponse($e, 400);
        }
        catch(OrderHandlerExceptionInterface $e) {
            return $this->createErrorResponse($e, 500);
        }
        catch (\Throwable $e) {
            return $this->createErrorResponse($e, 500);
        }

        return $this->responder->createResponse( $order );
    }



    protected function createErrorResponse (\Throwable $e, int $status) : ResponseInterface
    {
        $this->logger->warning($e->getMessage(), $this->throwableToArray($e));

        $response_header_name = $this->getResponseHeaderName();
        $response_header_value = get_class($e);

        $response = $this->responder->createErrorResponse( $e )
                                    ->withStatus($status)
                                    ->withHeader($response_header_name, $response_header_value);

        return $response;

    }



    protected function throwableToArray (\Throwable $e) : array
    {
        $result = array(
            'type'     => get_class($e),
            'message'  => $e->getMessage()
        );
        if ($this->debug) {
            $result['location'] = sprintf("%s:%s", $e->getFile(), $e->getLine());
        }

        return $result;
    }


}
