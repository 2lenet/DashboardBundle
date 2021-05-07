<?php

namespace Tkuska\DashboardBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testAddwidget()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/addWidget');
    }

    public function testRemovewidget()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/removeWidget');
    }
}
