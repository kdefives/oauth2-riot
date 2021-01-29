<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Riot as RiotProvider;
use League\OAuth2\Client\Provider\RiotUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class RiotTest extends TestCase
{
    /** @var RiotProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new RiotProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'url_authorization' => 'https://riot.com/as/authorization.oauth2',
            'url_token' => 'https://riot.com/as/token.oauth2',
            'url_user_info' => 'https://riot.com/idp/userinfo.openid'
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/as/token.oauth2', $uri['path']);
    }

    public function testUserData()
    {
        // Mock
        $response = [
            'client_id' => '12345',
            'uid' => 'toto123',
            'exp' => 1597685985,
        ];

        $token = $this->mockAccessToken();

        $provider = Phony::partialMock(RiotProvider::class);
        $provider->fetchResourceOwnerDetails->returns($response);
        $riot = $provider->get();

        // Execute
        $user = $riot->getResourceOwner($token);

        // Verify
        Phony::inOrder(
            $provider->fetchResourceOwnerDetails->called()
        );

        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);

        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('toto123', $user->getUid());
        $this->assertEquals(1597685985, $user->getExpirationTimestamp());

        $user = $user->toArray();

        $this->assertArrayHasKey('client_id', $user);
        $this->assertArrayHasKey('uid', $user);
        $this->assertArrayHasKey('exp', $user);
    }

    public function testErrorResponse()
    {
        // Mock
        $error_json = '{"error": {"code": 400, "message": "I am an error"}}';

        $response = Phony::mock('GuzzleHttp\Psr7\Response');
        $response->getHeader->returns(['application/json']);
        $response->getBody->returns($error_json);

        $provider = Phony::partialMock(RiotProvider::class);
        $provider->getResponse->returns($response);

        $riot = $provider->get();

        $token = $this->mockAccessToken();

        // Expect
        $this->expectException(IdentityProviderException::class);

        // Execute
        $user = $riot->getResourceOwner($token);

        // Verify
        Phony::inOrder(
            $provider->getResponse->calledWith($this->instanceOf('GuzzleHttp\Psr7\Request')),
            $response->getHeader->called(),
            $response->getBody->called()
        );
    }

    /**
     * @return AccessToken
     */
    private function mockAccessToken()
    {
        return new AccessToken([
            'access_token' => 'mock_access_token',
        ]);
    }
}
