<?php
namespace Germania\OrderDispatcher;

use Germania\OrderDispatcher\Exceptions\ItemInvalidUserDataException;
use Germania\OrderDispatcher\Exceptions\ItemNotOrderedException;

class FilterValidator implements ValidatorInterface
{

    /**
     * @var array
     */
    public $field_validation = array();


    /**
     * @param array $validation [description]
     */
    public function __construct( array $validation )
    {
        $this->setValidation($validation);
    }


    public function setValidation( array $validation )
    {
        $this->validation = $validation;
        return $this;
    }


    /**
     * @param  array  $input
     * @return bool
     */
    public function validate( array $input ) : array
    {
        $filtered_input = filter_var_array($input, $this->validation, (bool) "add_empty");
        return array_merge($input, $filtered_input);
    }
}
