<?php
namespace Germania\OrderDispatcher;

use Twig\Environment as TwigEnvironment;

class TwigRenderer implements RendererInterface
{

    use ContextTrait;

    /**
     * @var Twig\Environment
     */
    public $twig;


    /**
     * @param Twig\Environment
     */
    public function __construct( TwigEnvironment $twig, array $default_context = array())
    {
        $this->setTwigEnvironment($twig);
        $this->setContext($default_context);
    }


    /**
     * @inheritDoc
     */
    public function render( string $template, array $context = array()) : string
    {
        $context = $this->getContext($context);
        return $this->twig->render($template, $context);
    }


    /**
     * @param Twig\Environment
     */
    public function setTwigEnvironment( TwigEnvironment $twig ) {
        $this->twig = $twig;
        return $this;
    }

}
