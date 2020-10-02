<?php
namespace Germania\OrderDispatcher;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

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


    /**
     * @var string
     */
    public $response_header_name = "X-Order-Dispatch-Message";



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
     * Sets the response header name.
     *
     * @param string $header_name [description]
     */
    public function setResponseHeaderName( string $header_name )
    {
        $this->response_header_name = $header_name;
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
            $this->logger->warning($e->getMessage(), $this->throwableToArray($e));
            return $this->addHeader($response, $e )->withStatus(400);
        }
        catch(OrderHandlerExceptionInterface $e) {
            $this->logger->error($e->getMessage(), $this->throwableToArray($e));
            return $this->addHeader($response, $e)->withStatus(500);
        }
        catch (\Throwable $e) {
            $this->logger->alert($e->getMessage(), $this->throwableToArray($e));
            return $this->addHeader($response, $e)->withStatus(500);
        }


        $json_order = json_encode($order, \JSON_PRETTY_PRINT);
        $response->getBody()->write($json_order);

        $response = $response->withHeader('Content-type', 'application/json')
                             ->withStatus(200);

        return $response;

    }


    protected function throwableToArray (\Throwable $e) : array
    {
        return array(
            'type'     => get_class($e),
            'message'  => $e->getMessage(),
            'location' => sprintf("%s:%s", $e->getFile(), $e->getLine())
        );
    }



    protected function addHeader( ResponseInterface $response, $message) : ResponseInterface
    {
        if ($message instanceOf \Throwable) {
            $message = get_class($message);
        }

        $response = $response->withHeader($this->response_header_name, $message);
        return $response;
    }
}
