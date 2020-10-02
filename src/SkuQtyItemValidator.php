<?php
namespace Germania\OrderDispatcher;

use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;

class SkuQtyItemValidator implements ValidatorInterface
{
    /**
     * Items need at lease a 'sku' and a 'quantity'.
     *
     * @var array
     */
    public $field_validation = array(
        "sku"    =>  FILTER_SANITIZE_STRING,
        "quantity" =>  array ( "filter"=>FILTER_VALIDATE_INT, "options"=>["min_range"=>0])
    );


    /**
     * Perform user data validation and returns cleaned user data,
     * Other array values passed will be preserved and merged back into the result.
     *
     * @throws \Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException
     * @throws \Germania\OrderDispatcher\Exceptions\ItemNotOrderedException
     */
    public function validate( array $input ) : array
    {
        $filtered_input = filter_var_array($input, $this->field_validation, (bool) "add_empty");

        if (empty($filtered_input['sku'])) {
            throw new ItemInvalidUserDataException("SKU must not be empty");
        }

        if (false === $filtered_input['quantity']) {
            throw new ItemInvalidUserDataException("Invalid item quantity");
        }

        if (empty($filtered_input['quantity'])) {
            throw new ItemNotOrderedException("Quantity was 0");
        }

        return array_merge($input, $filtered_input);
    }
}
