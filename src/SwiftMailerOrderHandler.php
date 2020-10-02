<?php
namespace Germania\OrderDispatcher;

use Swift_Mailer;
use Swift_Message;
use Germania\OrderDispatcher\Exceptions\OrderHandlerRuntimeException;


class SwiftMailerOrderHandler implements OrderHandlerInterface
{

    use ContextTrait;

    /**
     * @var \Swift_Mailer
     */
    public $swift_mailer;

    /**
     * @var RendererInterface
     */
    public $renderer;

    /**
     * @var array
     */
    public $mail_config;


    /**
     * @param Swift_Mailer      $swift_mailer SwiftMailer instance
     * @param array             $mail_config  Mail configuration with mandatory `to` and `from` elements and optional `subject` and `template`
     * @param RendererInterface $renderer
     */
    public function __construct(Swift_Mailer $swift_mailer, array $mail_config, RendererInterface $renderer)
    {
        $this->setConfig($mail_config);
        $this->setSwiftMailer($swift_mailer);
        $this->setRenderer($renderer);

    }


    /**
     * @param RendererInterface $renderer Renderer
     */
    public function setRenderer( RendererInterface $renderer ) {
        $this->renderer = $renderer;
        return $this;
    }


    /**
     * @param Swift_Mailer $swift_mailer
     */
    public function setSwiftMailer( Swift_Mailer $swift_mailer ) {
        $this->swift_mailer = $swift_mailer;
        return $this;
    }


    /**
     * @param array $mail_config Mail configuration
     */
    public function setConfig( array $mail_config ) {
        if (empty($mail_config['from'])) {
            throw new \UnexpectedValueException("Element 'from' missing");
        }
        if (empty($mail_config['to'])) {
            throw new \UnexpectedValueException("Element 'to' missing");
        }

        $this->mail_config = $mail_config;
    }


    /**
     * @inheritDoc
     *
     * @throws OrderHandlerRuntimeException
     */
    public function handle( OrderInterface $order, array $context = array()) : bool
    {
        try {
            $context = $this->getContext($context);
            $message = $this->createSwiftMessage($order, $context);

            $mail_result = $this->swift_mailer->send($message);
            return (bool) $mail_result;
        }
        catch( \Throwable $e) {
            throw new OrderHandlerRuntimeException("Caught Throwable", 1, $e);
        }
    }




    protected function createSwiftMessage( OrderInterface $order, array $context = array()) : Swift_Message
    {
        $mail_subject = $this->createMailSubject($order, $context);
        $mailBody = $this->createMailBody($order, $context);

        $from = $this->mail_config['from'];
        $to   = $this->mail_config['to'];

        return (new Swift_Message( $mail_subject ))
          ->setFrom($from)
          ->setTo($to)
          ->setBody($mailBody, 'text/html');
    }



    protected function createMailSubject(OrderInterface $order, array $context = array()) : string
    {
        $customer_data = $order->getCustomerData();
        $company         = $customer_data['company']         ?? null ?: "Unbekannter Kunde";
        $retailer_number = $customer_data['retailer_number'] ?? null ?: "(ohne Kundennummer)";

        $werbepaket_name = $context['mailSubject'] ?? null ?: ($this->mail_config['subject'] ?? null ?: null);

        if (empty($werbepaket_name)) {
            return sprintf("Bestellung: %s/%s",
                $company,
                $retailer_number
            );
        }

        return sprintf("Bestellung zu %s: %s/%s",
            $werbepaket_name,
            $company,
            $retailer_number
        );

        return $mail_subject;
    }


    /**
     * @param  OrderInterface $order   [description]
     * @param  array          $context [description]
     * @return string
     *
     * @throws  RuntimeException
     */
    protected function createMailBody(OrderInterface $order, array $context = array()) : string
    {
        $customer_data         = $order->getCustomerData();
        $user_ordered_articles = $order->getItems();

        $mail_template = ($context['mailTemplate'] ?? null) ?: (($this->mail_config['template'] ?? null) ?: null);

        if (empty($mail_template)) {
            throw new \RuntimeException("No mail template defined");
        }

        return $this->renderer->render($mail_template, array_merge($context, [
            'customer'    => $customer_data,
            'orderItems'  => $user_ordered_articles,
            'datetimeNow' => date('d.m.Y, H:i:s')
        ]));

    }


}
