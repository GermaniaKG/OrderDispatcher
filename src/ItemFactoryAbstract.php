<?php
namespace Germania\OrderDispatcher;


abstract class ItemFactoryAbstract implements ItemFactoryInterface
{

    /**
     * inheritDoc
     */
    abstract public function createItem( array $ordered_item ) : ItemInterface;


}
