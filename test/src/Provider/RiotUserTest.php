<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\RiotUser;
use PHPUnit\Framework\TestCase;

class RiotUserTest extends TestCase
{
    public function testUserDefaults()
    {
        // Mock
        $user = new RiotUser([
            'client_id' => '12345',
            'uid' => 'toto123',
            'exp' => 1597685985,
        ]);

        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('toto123', $user->getUid());
        $this->assertEquals(1597685985, $user->getExpirationTimestamp());
    }
}
