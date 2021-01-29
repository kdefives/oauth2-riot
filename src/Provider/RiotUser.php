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
        return $this->response['client_id'];
    }

    /**
     * Get the UID for the user
     *
     * @return string|null
     */
    public function getUid()
    {
        return $this->response['uid'];
    }

    /**
     * Get the token expiration timestamp
     *
     * @return int|null
     */
    public function getExpirationTimestamp()
    {
        return $this->response['exp'];
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
