<?php

namespace MO\Bundle\MovieBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHome()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testMoviedetail()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/movieDetail');
    }

}
