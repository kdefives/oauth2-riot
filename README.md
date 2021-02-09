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

Example for knpu_oauth2_client declaration:

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

## Installation

To install, use composer:

```sh
composer require kdefives/oauth2-riot
```

## Usage

Read this README documentation to know how to integrate this provider : [https://github.com/knpuniversity/oauth2-client-bundle](https://github.com/knpuniversity/oauth2-client-bundle)

### Authorization Code Flow example using Symfony 5.2.2 (tested with PHP-7.4)

#### Authenticator declaration

```php
<?php
// app/src/Security/RiotAuthenticator.php

namespace App\Security;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
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
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class RiotAuthenticator used for Riot RSO authentication using knpuniversity/oauth2-client-bundle
 * @package App\Security
 */
class RiotAuthenticator extends SocialAuthenticator
{
    use TargetPathTrait;

    private $clientRegistry;
    private $em;
    private $router;

    public function __construct(ClientRegistry $clientRegistry,
                                EntityManagerInterface $em,
                                RouterInterface $router)
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

        // We check we have a matching user in our database, then return the user
        return $this->em->getRepository(Player::class)->findOneBy(
            ['puuid' => $riotUser->getPuuid()]
        );

        // If return null, login should fail
        //return null;
    }

    /**
     * @return OAuth2ClientInterface
     */
    private function getRiotClient()
    {
        return $this->clientRegistry
            // "facebook_main" is the key used in config/packages/knpu_oauth2_client.yaml
            ->getClient('riot_oauth');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Get initial target URI before redirection to RSO (login page or refresh token)
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        // Redirect to homepage by default
        if (!$targetPath) {
            $targetPath = $this->router->generate('homepage.display');
        }

        return new RedirectResponse($targetPath);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        //$message = strtr($exception->getMessageKey(), $exception->getMessageData());
        //return new Response($message, Response::HTTP_FORBIDDEN);

        // Redirect to homepage
        return new RedirectResponse(
            '/', // might be the site, where users choose their oauth provider
        );
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
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

#### Controller declaration
```php 
<?php
// app/src/Controller/RiotRsoController.php

namespace App\Controller;


use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RiotRsoController
 * @package App\Controller
 */
class RiotRsoController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect", name="connect_riot_start")
     *
     * @param ClientRegistry $clientRegistry
     * @return RedirectResponse
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // on Symfony 3.3 or lower, $clientRegistry = $this->get('knpu.oauth2.registry');

        // will redirect to Riot RSO
        return $clientRegistry
            ->getClient('riot_oauth') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect(
                ['openid'], // the scopes you want to access
                []
            );
    }

    /**
     * After going to Riot RSO, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/riot-oauth/callback", name="connect_riot_check")
     *
     * @param Request $request
     * @param ClientRegistry $clientRegistry
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        //return new Response("<html>Hello World</html>");
    }
}
```

#### Guard authenticator declaration (security.yaml)
```yaml
# app/config/packages/security.yaml

security:
    # https://symfony.com/doc/current/security.html#where-do-users-come-from-user-providers
    providers:
        # used to reload user from session & other features (e.g. switch_user)
        #in_memory: { memory: ~ }
        app_user_provider:
            entity:
                class: App\Entity\Player
                property: id
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            #anonymous: ~
            anonymous: true
            lazy: true
            provider: app_user_provider

            # activate different ways to authenticate
            # https://symfony.com/doc/current/security.html#firewalls-authentication

            # https://symfony.com/doc/current/security/impersonating_user.html
           # switch_user: true

            guard:
                authenticators:
                    - App\Security\RiotAuthenticator

    # Easy way to control access for large sections of your site
    # Note: Only the *first* access control that matches will be used
    access_control:
        - { path: ^/something, role: IS_AUTHENTICATED_REMEMBERED }
        - { path: ^/something-else, role: IS_AUTHENTICATED_REMEMBERED }
        - { path: ^/*, role: IS_AUTHENTICATED_ANONYMOUSLY }
```

#### Entity used for authentication
```php
<?php
// app/src/Entity/Player.php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=PlayerRepository::class)
 */
class Player implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * A PUUID should never change (even if region transfers become a thing for val too)
     */
    private $puuid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPuuid(): ?string
    {
        return $this->puuid;
    }

    public function setPuuid(string $puuid): self
    {
        $this->puuid = $puuid;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        //$roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->puuid;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}

```

## Testing

Tests can be run with:

```sh
composer test
```

Style checks can be run with:

```sh
composer check
```