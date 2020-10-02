<?php
namespace Germania\OrderDispatcher;

interface OrderHandlerInterface
{

    /**
     * @param  OrderInterface $order    Order instance
     * @param  array          $context  Optional: additional context variables
     *
     * @throws Germania\OrderDispatcher\Exceptions\OrderHandlerExceptionInterface
     */
    public function handle( OrderInterface $order, array $context = array()) : bool ;
}
