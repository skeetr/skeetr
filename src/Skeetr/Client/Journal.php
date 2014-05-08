<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client;

class Journal
{
    private $idleSince;
    private $data = array(
        'works' => 0, 'avg' => 0, 'errors' => 0, 'timeouts' => 0, 'idle' => 0,
        'disconnected' => 0, 'since' => 0, 'error' => null
    );

    public function __construct()
    {
        $this->data['since'] = time();
        $this->idleSince = $this->data['since'];
    }

    /**
     * Record a success event
     *
     * @param  integer $time mileseconds
     * @return integer Number of works succeed
     */
    public function addSuccess($time)
    {
        $total = ( $this->data['avg'] * $this->data['works'] )  + $time;
        $this->data['works']++;
        $this->data['avg'] = $total/$this->data['works'];

        return $this->data['works'];
    }

    /**
     * Record a error event
     *
     * @param  string  $error
     * @return integer Number of works errored
     */
    public function addError($msg)
    {
        $this->data['error'] = $msg;

        return ++$this->data['errors'];
    }

    /**
     * Record a timeout event
     *
     * @return integer Number of timeout events
     */
    public function addTimeout()
    {
        return ++$this->data['timeouts'];
    }

    /**
     * Record a lost connection event
     *
     * @param  string  $time number of mileseconds disconected
     * @return integer Total time disconected from server
     */
    public function addLostConnection($time)
    {
        return $this->data['disconnected'] += $time;
    }

    /**
     * Record a idle event
     *
     * @return integer Total time idle
     */
    public function addIdle()
    {
        $time = microtime(true) - $this->idleSince;
        $this->idleSince = microtime(true);

        return $this->data['idle'] += $time;
    }

    /**
     * Gets the number of works suscedd
     *
     * @return integer
     */
    public function getWorks()
    {
        return $this->data['works'];
    }

    /**
     * Gets the average time in milliseconds on make a job
     *
     * @return float
     */
    public function getAvgTime()
    {
        return $this->data['avg'];
    }

    /**
     * Gets the number of works errored
     *
     * @return integer
     */
    public function getErrors()
    {
        return $this->data['errors'];
    }

    /**
     * Gets the last error recorded
     *
     * @return integer
     */
    public function getLastError()
    {
        return $this->data['error'];
    }

   /**
     * Gets the number of timeout events
     *
     * @return integer
     */
    public function getTimeouts()
    {
        return $this->data['timeouts'];
    }

    /**
     * Gets the total time disconected from server
     *
     * @return integer
     */
    public function getLostConnection()
    {
        return $this->data['disconnected'];
    }

    /**
     * Gets the total time idle
     *
     * @return integer
     */
    public function getIdle()
    {
        return $this->data['idle'];
    }

    /**
     * Gets all records as array
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Gets all records as JSON
     *
     * @return array
     */
    public function getJson()
    {
        return json_encode($this->data);
    }
}
