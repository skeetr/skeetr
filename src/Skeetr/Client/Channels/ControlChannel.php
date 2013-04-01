<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\Channels;
use Skeetr\Client;
use Skeetr\Client\Channel;

class ControlChannel extends Channel 
{
    /**
     * Constructor
     *
     * @param Client $client
     * @param string $basename printf format, used in ControlChannel::autoSetChannel
     */
    public function __construct(Client $client, $basename) 
    {
        parent::__construct($client);
        $this->autoSetChannel($basename);
    }

    /**
     * Set the channel name based on the basename given in the constructor 
     *
     * @param string $channel
     */
    private function autoSetChannel($basename) 
    {
        $channel = sprintf($basename, $this->client->getId());
        $this->setChannel($channel);
    }

    /**
     * {@inheritdoc}
     */
    public function process(\GearmanJob $job) 
    {
        $start = microtime(true);

        $command = json_decode($job->workload(), true);

        $result = $this->executeCommand($command);
        $result['elapsed'] = microtime(true) - $start;

        return json_encode($result);
    }

    /**
     * Execute the command received from the Job
     *
     * @param array $command
     * @return array the response 
     */
    protected function executeCommand($command)
    {
        if ( !is_array($command) || !isset($command['command']) ) {
            return $this->returnError('Malformed command received.');
        }

        switch ($command['command']) {
            case 'journal': return $this->commandJournal($command);
            case 'shutdown': return $this->commandShutdown($command);
            default: return $this->returnError('Unknown command.');
        }
    }

    /**
     * Return client's journal as array
     *
     * @param array $command
     * @return array the response 
     */
    protected function commandJournal($options)
    {
        return $this->client->getJournal()->getData();
    }

    /**
     * Shutdown the client
     *
     * @param array $command
     * @return boolean the response 
     */
    protected function commandShutdown($options)
    {
        return array(
            'result' => $this->client->shutdown()
        );
    }

    /**
     * Return an error
     *
     * @param string $message
     * @return array the response 
     */
    protected function returnError($message)
    {
        return array(
            'error' => $message
        );
    }
}
