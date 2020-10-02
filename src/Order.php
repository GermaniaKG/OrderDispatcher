<?php
namespace Germania\OrderDispatcher;

class Order implements OrderInterface
{

    /**
     * @var array
     */
    public $customer_data = array();

    /**
     * @var iterable
     */
    public $items = array();



    /**
     * @param array    $customer_data Customer data
     * @param iterable $items         Ordered Items
     */
    public function __construct (array $customer_data, iterable $items)
    {
        $this->customer_data = $customer_data;
        $this->items = $items;
    }


    public function jsonSerialize()
    {
        return array(
            'customer' => $this->getCustomerData(),
            'items' => $this->getItems()
        );
    }


    /**
     * @inheritDoc
     */
    public function getCustomerData() : array
    {
        return $this->customer_data;
    }


    /**
     * @inheritDoc
     */
    public function getItems() : iterable
    {
        return $this->items;
    }

}
