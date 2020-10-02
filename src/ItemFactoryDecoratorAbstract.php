<?php
namespace Germania\OrderDispatcher;


abstract class ItemFactoryDecoratorAbstract implements ItemFactoryInterface
{

    /**
     * @var ItemFactoryInterface
     */
    protected $item_factory;



    /**
     * @inheritDoc
     */
    abstract public function createItem( array $order_item ) : ItemInterface;



    /**
     * @param ItemFactoryInterface $item_factory
     */
    public function setItemFactory( ItemFactoryInterface $item_factory )
    {
        $this->item_factory = $item_factory;
        return $this;
    }


    /**
     * @return ItemFactoryInterface
     */
    public function getItemFactory() : ItemFactoryInterface
    {
        return $this->item_factory;
    }
}
