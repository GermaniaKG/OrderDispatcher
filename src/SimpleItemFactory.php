<?php
namespace Germania\OrderDispatcher;


class SimpleItemFactory extends ItemFactoryAbstract implements ItemFactoryInterface
{

    /**
     * @inheritDoc
     * @return Item
     */
    public function createItem( array $ordered_item ) : ItemInterface
    {

        $item = new Item($ordered_item);

        return $item;

    }
}
