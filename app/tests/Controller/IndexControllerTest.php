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
}
