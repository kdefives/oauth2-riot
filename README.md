# oauth2-riot
Riot (RSO) OAuth 2.0 support for the PHP League's OAuth 2.0 Client

# Riot Provider for OAuth 2.0 Client [(based on Google Provider)](https://github.com/thephpleague/oauth2-google)

This package provides Riot OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

The following versions of PHP are supported.

* PHP 7.3
* PHP 7.4

Package to authenticate users with Riot ID accounts using RSO (Riot Sign On).

To use this package, it will be necessary to have a RSO account client ID and client
secret. These are referred in the RSO documentation : [https://www.riotgames.com/en/DevRel/rso](https://www.riotgames.com/en/DevRel/rso)

## Requirements
Declare parameters below in your knpu_oauth2_client.yaml:
* url_authorization (eg.: https://riot.com/as/authorization.oauth2)
* url_token (eg.: https://riot.com/as/token.oauth2)
* url_user_info (eg.: https://riot.com/idp/userinfo.openid)

Example for knpu_oauth2_client declaration :
```yaml
# file : app\config\packages\knpu_oauth2_client.yaml

knpu_oauth2_client:
    clients:
        # configure your clients as described here: https://github.com/knpuniversity/oauth2-client-bundle#configuration
        # will create service: "knpu.oauth2.client.foo_bar_oauth"
        # an instance of: KnpU\OAuth2ClientBundle\Client\OAuth2Client
        riot_oauth:
            type: generic
            provider_class: League\OAuth2\Client\Provider\Riot

            # optional: a class that extends OAuth2Client
            # client_class: Some\Custom\Client

            # optional: if your provider has custom constructor options
            provider_options:
                url_authorization: '%env(URL_AUTHORIZATION)%'
                url_token: '%env(URL_TOKEN)%'
                url_user_info: '%env(URL_USERINFO)%'

            # now, all the normal options!
            client_id: '%env(riot_client_id)%'
            client_secret: '%env(riot_client_secret)%'
            redirect_route: connect_riot_check
            redirect_params: {}
```

## Usage
Read this README documentation to know how to integrate this provider : [https://github.com/knpuniversity/oauth2-client-bundle](https://github.com/knpuniversity/oauth2-client-bundle)

## Installation

To install, use composer:

```sh
composer require kdefives/oauth2-riot
```

## Usage

### Authorization Code Flow example using Symfony 5.2.2

#### Authenticator declaration:

```php
<?php


namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\RiotUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class RiotAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_riot_check';
    }

    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true

        // For Symfony lower than 3.4 the supports method need to be called manually here:
        // if (!$this->supports($request)) {
        //     return null;
        // }

        return $this->fetchAccessToken($this->getRiotClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var RiotUser $riotUser */
        $riotUser = $this->getRiotClient()
            ->fetchUserFromToken($credentials);

        // We check we have a matching user
        $existingUser = $this->em->getRepository(User::class)
            ->findOneByLogin($riotUser->getUid());

        if ($existingUser) {
            return $existingUser;
        }

        // If user not in the database, login should fail
        return null;
    }

    /**
     * @return OAuth2Client
     */
    private function getRiotClient()
    {
        return $this->clientRegistry
            // "facebook_main" is the key used in config/packages/knpu_oauth2_client.yaml
            ->getClient('riot_oauth');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('my.route');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
```

#### Controller declaration:
TODO...

#### Guard authenticator declaration (security.yaml)
TODO...

## Testing

Tests can be run with:

```sh
composer test
```

Style checks can be run with:

```sh
composer check
```

