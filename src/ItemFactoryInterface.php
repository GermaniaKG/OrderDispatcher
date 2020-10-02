<?php
namespace Germania\OrderDispatcher;

interface ItemFactoryInterface
{


    /**
     * @param  array  $order_item
     * @return ItemInterface
     *
     * @throws \Germania\OrderDispatcher\Exceptions\ItemExceptionInterface
     */
    public function createItem( array $order_item ) : ItemInterface;


}
