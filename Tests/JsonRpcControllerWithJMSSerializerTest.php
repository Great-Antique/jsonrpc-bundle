<?php

namespace Wa72\JsonRpcBundle\Tests;

use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Wa72\JsonRpcBundle\Tests\Fixtures\Testparameter;

/**
 * @group jmsSerializer
 */
class JsonRpcControllerWithJMSSerializerTest extends JsonRpcControllerTest
{

    public function setUp()
    {
        if (!class_exists(Serializer::class)) {
            $this->markTestSkipped('JMS Serializer is not installed');
        }

        parent::setUp();
    }

    public function testParametersWithObjects()
    {
        $arg3 = new Testparameter('abc');
        $arg3->setB('def');
        $arg3->setC('ghi');
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'testParameterTypes',
            'method'  => 'wa72_jsonrpc.testservice:testParameterTypes',
            'params'  => array('arg1' => array(), 'arg2' => new \stdClass(), 'arg3' => $arg3),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('abcdefghi', $response['result']);
    }

    protected function makeRequest($controller, $requestdata)
    {
        /** @var \JMS\Serializer\Serializer $serializer */
        $serializer = static::$kernel->getContainer()->get('jms_serializer');

        return json_decode(
            $controller->execute(
                new Request(
                    array(),
                    array(),
                    array(),
                    array(),
                    array(),
                    array(),
                    $serializer->serialize($requestdata, 'json')
                )
            )->getContent(),
            true
        );
    }

}
