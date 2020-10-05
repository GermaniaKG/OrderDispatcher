<?php
namespace Germania\OrderDispatcher;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class JsonResponder implements ResponderInterface
{

    /**
     * @var ResponseFactoryInterface
     */
    public $response_factory;


    /**
     * @var boolean
     */
    public $debug = false;


    /**
     * @var int
     */
    public $json_options = \JSON_PRETTY_PRINT;


    /**
     * Response content type
     * @var string
     */
    public $response_content_type = 'application/json';


    /**
     * @param ResponseFactoryInterface $response_factory
     * @param int                      $json_options
     * @param bool|boolean             $debug
     */
    public function __construct(ResponseFactoryInterface $response_factory, int $json_options = null, bool $debug = false)
    {
        $this->response_factory = $response_factory;
        $this->json_options = $json_options ?: $this->json_options;
        $this->debug = $debug;
    }



    /**
     * @inheritDoc
     */
    public function createResponse( $thingy) : ResponseInterface
    {
        if (!$thingy instanceOf \JsonSerializable) {
            $msg = sprintf("Expected JsonSerializable instance, instead got '%s'.", get_type($e));
            throw new \InvalidArgumentException($msg);
        }

        $response = $this->response_factory->createResponse()
                                           ->withHeader('Content-type', $this->response_content_type)
                                           ->withStatus(200);

        $json_thingy = json_encode($thingy, $this->json_options);
        $response->getBody()->write($json_thingy);

        return $response;
    }



    /**
     * @inheritDoc
     */
    public function createErrorResponse( \Throwable $e) : ResponseInterface
    {
        $exceptions = array($this->throwableToArray($e));
        while ($this->debug and $e = $e->getPrevious()) {
            $exceptions[] = $this->throwableToArray($e);
        }

        $result = array(
            'errors' => $exceptions
        );

        $json_error = json_encode($result, $this->json_options);


        $response = $this->response_factory->createResponse()
                                           ->withHeader('Content-type', $this->response_content_type)
                                           ->withStatus(500);

        $response->getBody()->write( $json_error );

        return $response;
    }




    protected function throwableToArray (\Throwable $e) : array
    {
        $result = array(
            'type'     => get_class($e),
            'message'  => $e->getMessage()
        );
        if ($this->debug) {
            $result['location'] = sprintf("%s:%s", $e->getFile(), $e->getLine());
        }

        return $result;
    }

}
