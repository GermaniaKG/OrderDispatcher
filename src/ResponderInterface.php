<?php
namespace Germania\OrderDispatcher;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponderInterface
{

    /**
     * Creates ResponseInterface.
     *
     * @param  mixed $result
     * @return ResponseInterface
     */
    public function createResponse( $result ) : ResponseInterface;


    /**
     * Creates an Error representation.
     *
     * @param  \Throwable $e
     * @return ResponseInterface
     */
    public function createErrorResponse( \Throwable $e) : ResponseInterface;
}
