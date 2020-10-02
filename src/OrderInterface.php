<?php
namespace Germania\OrderDispatcher;

interface OrderInterface extends \JsonSerializable
{

    /**
     * Returns the user data.
     * @return array
     */
    public function getCustomerData() : array;


    /**
     * Returns the ordered items
     * @return iterable
     */
    public function getItems() : iterable;
}
