<?php
namespace Germania\OrderDispatcher;

trait ContextTrait
{

    /**
     * @var array
     */
    public $default_context = array();


    /**
     * @inheritDoc
     */
    public function getContext( array $context = array()) : array
    {
        return array_merge($this->default_context, $context);
    }

    /**
     * @inheritDoc
     */
    public function setContext( array $context )
    {
        $this->default_context = $context;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function addContext( array $context )
    {
        $this->default_context = array_merge($this->default_context, $context);
        return $this;
    }


}
