<?php

declare(strict_types=1);

namespace VonageTest\Client;

use VonageTest\VonageTestCase;
use Vonage\Client;
use Vonage\Client\APIResource;

class APIResourceTest extends VonageTestCase
{
    public function testOverridingBaseUrlUsesClientApiUrl(): void
    {
        /** @var mixed $mockClient */
        $mockClient = $this->prophesize(Client::class);
        $mockClient->getApiUrl()->willReturn('https://test.domain');

        $resource = new APIResource();
        $resource->setClient($mockClient->reveal());

        $this->assertSame('https://test.domain', $resource->getBaseUrl());
    }

    public function testOverridingBaseUrlManuallyWorks(): void
    {
        $resource = new APIResource();
        $resource->setBaseUrl('https://test.domain');

        $this->assertSame('https://test.domain', $resource->getBaseUrl());
    }

    public function testNotOverridingBaseURLReturnsBlank(): void
    {
        $resource = new APIResource();
        $this->assertSame('', $resource->getBaseUrl());
    }
}
