<?php
namespace tests;

use Germania\OrderDispatcher\SwiftMailerOrderHandler;
use Germania\OrderDispatcher\RendererInterface;
use Germania\OrderDispatcher\OrderHandlerInterface;
use Swift_Mailer;
use Swift_Message;


use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

class SwiftMailerOrderHandlerTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;


    public function testInstantiation()
    {
        $renderer_mock = $this->prophesize( RendererInterface::class );
        $renderer = $renderer_mock->reveal();

        $swiftmailer_mock = $this->prophesize(Swift_Mailer::class);
        $swiftmailer = $swiftmailer_mock->reveal();

        $mail_config = array('to' => 'root', 'from' => 'me');

        $sut = new SwiftMailerOrderHandler($swiftmailer, $mail_config, $renderer);

        $this->assertInstanceOf(OrderHandlerInterface::class, $sut);
    }


    /**
     * @dataProvider provideInvalidMailConfiguration
     */
    public function testExceptionOnInvalidMailConfiguration($invalid_mail_config)
    {
        $renderer_mock = $this->prophesize( RendererInterface::class );
        $renderer = $renderer_mock->reveal();

        $swiftmailer_mock = $this->prophesize(Swift_Mailer::class);
        $swiftmailer = $swiftmailer_mock->reveal();

        $this->expectException( \UnexpectedValueException::class );
        new SwiftMailerOrderHandler($swiftmailer, $invalid_mail_config, $renderer);
    }


    public function provideInvalidMailConfiguration()
    {
        return array(
            [ array('to' => 'root') ],
            [ array('from' => 'me') ]
        );
    }




}
