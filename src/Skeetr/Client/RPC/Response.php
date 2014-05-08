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

class Response
{
    private $id;
    private $result = [];

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
     * Sets result
     *
     * @param array $result response result
     */
    public function setResult(Array $result)
    {
        $this->result = $result;
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
     * Get result
     *
     * @return array
     */
    public function getResult()
    {
        return $this->result;
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
            'result' => $this->result
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
