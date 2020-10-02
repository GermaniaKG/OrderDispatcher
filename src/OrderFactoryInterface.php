<?php
namespace Germania\OrderDispatcher;


interface OrderFactoryInterface
{

    /**
     * @param  array  $input User input
     * @return OrderInterface
     *
     * @throws Germania\OrderDispatcher\Exceptions\OrderFactoryExceptionInterface
     */
    public function createOrder( array $input) : OrderInterface;


    /**
     * Returns the item factory.
     *
     * @return ItemFactoryInterface
     */
    public function getItemFactory() : ItemFactoryInterface;


    /**
     * Sets the item factory.
     *
     * @param ItemFactoryInterface $item_factory
     */
    public function setItemFactory(ItemFactoryInterface $item_factory);
}
