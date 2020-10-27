<?php
namespace Germania\OrderDispatcher;

use Swift_Mailer;
use Swift_Message;
use Germania\OrderDispatcher\Exceptions\OrderHandlerRuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SwiftMailerOrderHandler implements OrderHandlerInterface, LoggerAwareInterface
{

    use ContextTrait, LoggerAwareTrait;

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
    public function __construct(Swift_Mailer $swift_mailer, array $mail_config, RendererInterface $renderer, LoggerInterface $logger = null)
    {
        $this->setConfig($mail_config);
        $this->setSwiftMailer($swift_mailer);
        $this->setRenderer($renderer);
        $this->setLogger($logger ?: new NullLogger);

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
        catch (OrderHandlerInterface $e) {
            throw $e;
        }
        catch( \Throwable $e) {
            $msg = sprintf("SwiftMailerOrderHandler: unexpected Throwable '%s'", get_class($e));
            $this->logger->error($msg, [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'location' => join(":", [$e->getFile(),$e->getLine()])
            ]);
            throw new OrderHandlerRuntimeException($msg, 1, $e);
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



    public function createMailSubject(OrderInterface $order, array $context = array()) : string
    {
        $mail_subject = $context['mailSubject'] ?? null ?: ($this->mail_config['subject'] ?? null ?: null);
        if (empty($mail_subject)) {
            throw new \RuntimeException("No mail subject defined");
        }

        $context = array_merge($order->getCustomerData(), $context);

        // Stolen here:
        // https://github.com/Seldaek/monolog/blob/master/src/Monolog/Processor/PsrLogMessageProcessor.php
        $replacements = [];
        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (strpos($mail_subject, $placeholder) === false) {
                continue;
            }

            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                $replacements[$placeholder] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements[$placeholder] = $val->format("Y-m-d\TH:i:s.uP");
            } elseif (is_object($val)) {
                $replacements[$placeholder] = '[object '.get_class($val).']';
            } elseif (is_array($val)) {
                $replacements[$placeholder] = 'array'.@json_encode($val);
            } else {
                $replacements[$placeholder] = '['.gettype($val).']';
            }
        }
        $mail_subject = strtr($mail_subject, $replacements);
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
