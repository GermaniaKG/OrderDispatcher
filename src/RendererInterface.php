<?php
namespace Germania\OrderDispatcher;

interface RendererInterface
{

    /**
     * @param  string $template
     * @param  array  $context Optional: additional context variables
     * @return string|null
     */
    public function render( string $template, array $context = array()) : ?string;


}
