<?php
namespace Germania\OrderDispatcher;


abstract class OrderFactoryAbstract implements OrderFactoryInterface
{

    use ValidatorTrait;

    /**
     * @var ItemFactoryInterface
     */
    public $item_factory;



    /**
     * @inheritDoc
     */
    abstract public function createOrder( array $input) : OrderInterface;


    /**
     * @inheritDoc
     */
    public function setItemFactory(ItemFactoryInterface $item_factory)
    {
        $this->item_factory = $item_factory;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getItemFactory() : ItemFactoryInterface
    {
        return $this->item_factory;
    }




}
