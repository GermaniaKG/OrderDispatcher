<?php
namespace tests;

use Germania\OrderDispatcher\TwigRenderer;
use Germania\OrderDispatcher\RendererInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

use Twig\Environment as TwigEnvironment;

class TwigRendererTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;


    public function testInstantiation()
    {
        $twig_stub = $this->prophesize(TwigEnvironment::class);
        $twig = $twig_stub->reveal();

        $sut = new TwigRenderer($twig);
        $this->assertInstanceOf(RendererInterface::class, $sut);
    }

}

