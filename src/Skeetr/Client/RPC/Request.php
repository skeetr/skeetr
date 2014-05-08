<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\RPC;

use UnexpectedValueException;

class Request
{
    private $id;
    private $method;
    private $params = [];

    /**
     * Configure the request based on a JSON
     *
     * @param  string  $json
     * @return boolean
     */
    public static function fromJSON($json)
    {
        $request = new static();
        $data = json_decode($json, true);

        if (!$data) {
            throw new UnexpectedValueException(sprintf(
                'Unexpected message, invalid JSON from nginx: "%s"', $json
            ));
        }

        if (isset($data['id'])) {
            $request->setId($data['id']);
        }

        if (isset($data['method'])) {
            $request->setMethod($data['method']);
        }

        if (isset($data['params'])) {
            $request->setPrams($data['params']);
        }

        return $request;
    }

    /**
     * Sets the request id
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets method
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Sets params
     *
     * @param array $params request params
     */
    public function setPrams(Array $params)
    {
        $this->params = $params;
    }


    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns a array with the values of this Request
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'params' => $this->params
        ];
    }

    /**
     * Returns a JSON with the values of this Request
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }
}
