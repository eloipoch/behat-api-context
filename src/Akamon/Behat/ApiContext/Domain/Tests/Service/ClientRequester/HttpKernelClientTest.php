<?php

namespace Akamon\Behat\ApiContext\Domain\Tests\Service\ClientRequester;

use Akamon\Behat\ApiContext\Domain\Service\ClientRequester\HttpKernelClientRequester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpKernelClientTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $httpKernel = \Mockery::mock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');

        $client = new HttpKernelClientRequester();
        $client->setHttpKernel($httpKernel);

        $request = new Request();
        $response = new Response();

        $httpKernel->shouldReceive('handle')->with($request)->once()->andReturn($response);

        $this->assertSame($response, $client->request($request));
    }
}
