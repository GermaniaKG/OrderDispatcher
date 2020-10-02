<?php
namespace Germania\OrderDispatcher;

trait ValidatorTrait
{


    /**
     * @var ValidatorInterface
     */
    protected $validator;



    /**
     * Sets the validator.
     *
     * @param ValidatorInterface $validator
     */
    public function setValidator( ValidatorInterface $validator )
    {
        $this->validator = $validator;
        return $this;
    }


    /**
     * Returns the validator.
     */
    public function getValidator() : ValidatorInterface
    {
        if (!$this->validator) {
            $this->validator = function( array $i) { return $i; };
        }
        return $this->validator;
    }

}
