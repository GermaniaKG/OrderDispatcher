<?php
namespace Germania\OrderDispatcher;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

use Germania\OrderDispatcher\Exceptions\OrderHandlerRuntimeException;


class OrderHandlerChain implements OrderHandlerInterface
{
    use LoggerAwareTrait,
        ContextTrait;



    /**
     * @var array
     */
    public $order_handlers = array();


    /**
     * @param array                $order_handlers Order handlers
     * @param LoggerInterface|null $logger         Optional: PSR-3 Logger
     */
    public function __construct( array $order_handlers = array(), LoggerInterface $logger = null)
    {
        $this->setLogger($logger);

        foreach($order_handlers as $order_handler):
            $this->add( $order_handler);
        endforeach;
    }


    /**
     * Add an OrderHandler to the chain.
     *
     * @param OrderHandlerInterface $order_handler
     */
    public function add(OrderHandlerInterface $order_handler )
    {
        array_push($this->order_handlers, $order_handler);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function handle( OrderInterface $order, array $context = array()) : bool
    {
        $context = $this->getContext($context);
        $results = array();
        $exceptions = array();

        foreach($this->order_handlers as $order_handler):
            try {
                $handler_result = $order_handler->handle($order, $context);
                $results[] = $handler_result;
            }
            catch( \Throwable $e) {
                $exceptions[] = $e;
            }
        endforeach;

        if (empty($exceptions)) {
            return !in_array(false, $results);
        }

        $msg = "Caught Throwables in handler chain";
        $new_exception = new OrderHandlerRuntimeException($msg, 1);
        $new_exception->setCaughtExceptions($exceptions);
        throw $new_exception;

    }





}
