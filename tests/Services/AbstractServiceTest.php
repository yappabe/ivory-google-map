<?php

/*
 * This file is part of the Ivory Google Map package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\Tests\GoogleMap\Services;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;

/**
 * Abstract service test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class AbstractServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\GoogleMap\Services\AbstractService */
    protected $service;

    /** @var MessageFactory */
    protected $messageFactory;

    /** @var HttpClient */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->messageFactory = $this->getMock('Http\Message\MessageFactory');
        $this->client = $this->getMock('Http\Client\HttpClient');

        $this->service = $this->getMockBuilder('Ivory\GoogleMap\Services\AbstractService')
            ->setConstructorArgs(array($this->client, $this->messageFactory, 'http://foo'))
            ->getMockForAbstractClass();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->messageFactory);
        unset($this->service);
    }

    public function testDefaultState()
    {
        $this->assertSame($this->messageFactory, $this->service->getMessageFactory());
        $this->assertSame('http://foo', $this->service->getUrl());
        $this->assertFalse($this->service->isHttps());
        $this->assertSame('json', $this->service->getFormat());
        $this->assertInstanceOf('Ivory\GoogleMap\Services\Utils\XmlParser', $this->service->getXmlParser());
        $this->assertFalse($this->service->hasBusinessAccount());
        $this->assertNull($this->service->getBusinessAccount());
    }

    public function testInitialState()
    {
        $xmlParser = $this->getMock('Ivory\GoogleMap\Services\Utils\XmlParser');

        $businessAccount = $this->getMockBuilder('Ivory\GoogleMap\Services\BusinessAccount')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = $this->getMockBuilder('Ivory\GoogleMap\Services\AbstractService')
            ->setConstructorArgs(array($this->client, $this->messageFactory, 'http://bar', true, 'xml', $xmlParser, $businessAccount))
            ->getMockForAbstractClass();

        $this->assertSame($this->messageFactory, $this->service->getMessageFactory());
        $this->assertSame('https://bar', $this->service->getUrl());
        $this->assertTrue($this->service->isHttps());
        $this->assertSame('xml', $this->service->getFormat());
        $this->assertSame($xmlParser, $this->service->getXmlParser());
        $this->assertTrue($this->service->hasBusinessAccount());
        $this->assertSame($businessAccount, $this->service->getBusinessAccount());
    }

    public function testMessageFactory()
    {
        $messageFactory = $this->getMock('Http\Message\MessageFactory');
        $this->service->setMessageFactory($messageFactory);

        $this->assertSame($messageFactory, $this->service->getMessageFactory());
    }

    public function testHttps()
    {
        $this->service->setHttps(true);

        $this->assertTrue($this->service->isHttps());
    }

    /**
     * @expectedException \Ivory\GoogleMap\Exception\ServiceException
     * @expectedExceptionMessage The service https flag must be a boolean value.
     */
    public function testHttpsWithInvalidValue()
    {
        $this->service->setHttps('foo');
    }

    public function testUrlWithHttps()
    {
        $this->service->setHttps(true);
        $this->assertSame('https://foo', $this->service->getUrl());
    }

    /**
     * @expectedException \Ivory\GoogleMap\Exception\ServiceException
     * @expectedExceptionMessage The service url must be a string value.
     */
    public function testUrlWithInvalidValue()
    {
        $this->service->setUrl(true);
    }

    public function testFormatWithJsonAndXml()
    {
        $this->service->setFormat('xml');
        $this->assertSame('xml', $this->service->getFormat());

        $this->service->setFormat('json');
        $this->assertSame('json', $this->service->getFormat());
    }

    /**
     * @expectedException \Ivory\GoogleMap\Exception\ServiceException
     * @expectedExceptionMessage The service format can only be : json, xml.
     */
    public function testFormatWithInvalidValue()
    {
        $this->service->setFormat('foo');
    }

    public function testXmlParser()
    {
        $xmlParser = $this->getMock('Ivory\GoogleMap\Services\Utils\XmlParser');
        $this->service->setXmlParser($xmlParser);

        $this->assertSame($xmlParser, $this->service->getXmlParser());
    }

    public function testBusinessAccount()
    {
        $businessAccount = $this->getMockBuilder('Ivory\GoogleMap\Services\BusinessAccount')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service->setBusinessAccount($businessAccount);

        $this->assertTrue($this->service->hasBusinessAccount());
        $this->assertSame($businessAccount, $this->service->getBusinessAccount());

        $this->service->setBusinessAccount();

        $this->assertFalse($this->service->hasBusinessAccount());
        $this->assertNull($this->service->getBusinessAccount());
    }

    public function testSignUrlWithoutBusinessAccount()
    {
        $method = new \ReflectionMethod($this->service, 'signUrl');
        $method->setAccessible(true);

        $url = 'http://maps.googleapis.com/maps/api/staticmap?center=%E4%B8%8A%E6%B5%B7+%E4%B8%AD%E5%9C%8B&size=640x640&zoom=10&sensor=false';

        $this->assertSame($url, $method->invoke($this->service, $url));
    }

    public function testSignUrlWithBusinessAccount()
    {
        $url = 'http://maps.googleapis.com/maps/api/staticmap?center=%E4%B8%8A%E6%B5%B7+%E4%B8%AD%E5%9C%8B&size=640x640&zoom=10&sensor=false';

        $businessAccount = $this->getMockBuilder('Ivory\GoogleMap\Services\BusinessAccount')
            ->disableOriginalConstructor()
            ->getMock();

        $businessAccount
            ->expects($this->once())
            ->method('signUrl')
            ->with($this->equalTo($url))
            ->will($this->returnValue('url'));

        $this->service->setBusinessAccount($businessAccount);

        $method = new \ReflectionMethod($this->service, 'signUrl');
        $method->setAccessible(true);

        $this->assertSame('url', $method->invoke($this->service, $url));
    }
}
