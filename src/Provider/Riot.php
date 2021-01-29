<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Riot extends AbstractProvider
{
    use BearerAuthorizationTrait;

    private $urlAuthorization; // Riot RSO authorization URL
    private $urlToken; // Riot RSO token URL
    private $urlUserInfo; // Riot RSO UserInfo URL

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $this->urlAuthorization = $options['url_authorization'];
        $this->urlToken = $options['url_token'];
        $this->urlUserInfo = $options['url_user_info'];
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->urlAuthorization;
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->urlToken;
    }


    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->urlUserInfo;
    }

    protected function getDefaultScopes()
    {
        // "openid" MUST be the first scope in the list.
        return [
            'openid',
            'profile',
            'email',
        ];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        // @codeCoverageIgnoreStart
        if (empty($data['error'])) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $code = 0;
        $error = $data['error'];

        if (is_array($error)) {
            $code = $error['code'];
            $error = $error['message'];
        }

        throw new IdentityProviderException($error, $code, $data);
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new RiotUser($response);
    }
}
