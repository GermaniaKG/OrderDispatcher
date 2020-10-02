<?php
namespace tests;

trait LoggerTrait
{

    public $logger;

    protected function getLogger()
    {
        if ($this->logger) {
            return $this->logger;
        }
        $this->logger = $this->createLaminasLogger();
        return $this->logger;
    }

    protected function createLaminasLogger()
    {
        $loglevel = \Laminas\Log\Logger::DEBUG;
        $filter = new \Laminas\Log\Filter\Priority( $loglevel );

        $writer = new \Laminas\Log\Writer\Stream('php://output');
        $writer->addFilter($filter);

        $laminasLogLogger = new \Laminas\Log\Logger;
        $laminasLogLogger->addWriter($writer);

        return new \Laminas\Log\PsrLoggerAdapter($laminasLogLogger);
    }
}
