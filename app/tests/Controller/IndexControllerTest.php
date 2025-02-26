<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IndexControllerTest extends WebTestCase
{
    public function testTest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/test');

        self::assertResponseIsSuccessful();
    }

    public function testTestMessage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test');

        self::assertResponseIsSuccessful();
    }
}
