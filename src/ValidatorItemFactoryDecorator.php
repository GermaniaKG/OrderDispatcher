<?php
namespace Germania\OrderDispatcher;


class ValidatorItemFactoryDecorator extends ItemFactoryDecoratorAbstract implements ItemFactoryInterface
{

    use ValidatorTrait;


    /**
     * @param ItemFactoryInterface $item_factory Item factory component
     * @param ValidatorInterface   $validator    Callable that returns array
     */
    public function __construct(ItemFactoryInterface $item_factory, ValidatorInterface $validator)
    {
        $this->setValidator( $validator );
        $this->setItemFactory( $item_factory );
    }


    /**
     * Calls the validator before sending to Item factory component
     * @inheritDoc
     */
    public function createItem( array $order_item ) : ItemInterface
    {
        $order_item = $this->getValidator()->validate($order_item);

        return $this->getItemFactory()->createItem( $order_item);
    }


}
