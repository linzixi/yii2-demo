<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/8/28 12:53
 */
use terry\nlp\RpcService;

class RpcServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testService()
    {
        $hosts = RpcService::getServiceHost('sim');
        $this->assertTrue(is_array($hosts), 'get rpc hosts returned array');
        $this->assertGreaterThan(0, count($hosts), 'get rpc hosts cannot return empty');
    }

    public function testClient()
    {
        $res = RpcService::getServiceClient('sim');
        $this->assertTrue($res instanceof \Nlp\NlpClient, 'get service client show returned \Nlp\NlpClient instance');
    }

    public function testRequest()
    {
        $title = '长安标致雪铁龙DS 5';
        $content = '【长安标致雪铁龙DS 5】科幻，应该是对DS 5最恰当的形容词，除了旅行版以外，这是国内在售为数不多的两厢中型车。法国人在设计上从来都是走在全世界的前面，如此大胆造型和细节设计在中型车里独树一帜。内饰同样足够惊艳和科幻，大量应用了不规则的设计，层次感特别丰富';
        $req = new \Nlp\MgClassifyRequest([
            'title' => $title,
            'content' => $content,
            'type' => "short",
        ]);

        $reply = RpcService::request('ContentCategory', $req);
        $this->assertEquals("汽车", $reply->getRes());

    }

    public function testSetServiceHost()
    {
        RpcService::setServiceHost('nlp-national', ['127.0.0.1:50051']);
        $res = RpcService::getServiceHost('EmTags');
        $this->assertEquals('127.0.0.1:50051', $res[0]);
    }

}