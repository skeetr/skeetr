<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\RPC\Method;

use Skeetr\Client\RPC\Method;
use Skeetr\Client\RPC\Request;
use Skeetr\Client\HTTP;
use Skeetr\Client\Handler\Error;
use Skeetr\Runtime\Manager;
use http\Message\Body;

class Process extends Method
{
    private $callback;
    private $runtime = true;

    /**
     * Constructor
     *
     * @param callback $callback
     */
    public function __construct(Callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Request $request)
    {
        $start = microtime(true);

        $httpRequest = new HTTP\Request();

        $body = new Body();
        $httpResponse = new HTTP\Response;
        $httpResponse->setBody($body);

        try {
            $result = $this->runCallback($httpRequest, $httpResponse);
            $body->append($result);
        } catch (\Exception $e) {
            $httpResponse->setResponseCode(500);

            //TODO: Maybe implement something more complex, with better error reporting?
            Error::printException($e, false);
            $body->append(sprintf('Error: %s', $e->getMessage()));
        }

        $this->prepareResponse($httpResponse);

        return $httpResponse->toArray();
    }

    private function runCallback(HTTP\Request $request, HTTP\Response $response)
    {
        $cb = $this->callback;

        return $cb($request, $response);
    }

    /**
     * Set the headers and the response code to a given $response, class is reset after.
     *
     * @param  HTTP\Response $response
     * @return boolean
     */
    private function prepareResponse(HTTP\Response $response)
    {
        if ( !Manager::loaded() ) return false;
        session_write_close();

        $values = Manager::values();
        if ( isset($values['header']) ) {
            if ( isset($values['header']['code']) && $values['header']['code'] ) {
                $response->setResponseCode($values['header']['code']);
            }

            if ( isset($values['header']['list']) ) {
                foreach ($values['header']['list'] as $headers) {
                    foreach ($headers as $header) {
                        $response->addHeader($header, false);
                    }
                }
            }
        }

        Manager::reset();
    }
}
