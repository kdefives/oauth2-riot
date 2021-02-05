<?php

namespace League\OAuth2\Client\Provider;

class RiotUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId()
    {
        return $this->getPuuid();
    }

    /**
     * Get the PUUID of player
     *
     * @return string
     */
    public function getPuuid()
    {
        return $this->response['puuid'];
    }

    /**
     * Get the GameName of player
     *
     * @return string
     */
    public function getGameName()
    {
        return $this->response['gameName'];
    }

    /**
     * Get the tagLine of player
     *
     * @return string
     */
    public function getTagLine()
    {
        return $this->response['tagLine'];
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    private function getResponseValue($key)
    {
        if (array_key_exists($key, $this->response)) {
            return $this->response[$key];
        }
        return null;
    }
}
