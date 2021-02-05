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
            'puuid' => '12345azerty',
            'gameName' => 'playerName',
            'tagLine' => 'EUW',
        ]);

        $this->assertEquals('12345azerty', $user->getId());
        $this->assertEquals('12345azerty', $user->getPuuid());
        $this->assertEquals('playerName', $user->getGameName());
        $this->assertEquals('EUW', $user->getTagLine());
    }
}
