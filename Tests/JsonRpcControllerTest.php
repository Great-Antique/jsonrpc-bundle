<?php

namespace Wa72\JsonRpcBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Wa72\JsonRpcBundle\Controller\JsonRpcController;

class JsonRpcControllerTest extends KernelTestCase
{

    /**
     * @var \Wa72\JsonRpcBundle\Controller\JsonRpcController
     */
    protected $controller;

    /**
     * @var \Wa72\JsonRpcBundle\Controller\JsonRpcController
     */
    protected $controllerFromContainer;

    public function setUp()
    {
        require_once __DIR__.'/Fixtures/app/Wa72JsonRpcBundleTestKernel.php';

        $_SERVER['KERNEL_DIR'] = __DIR__.'/Fixtures/app';
        $_SERVER['KERNEL_CLASS'] = \Wa72JsonRpcBundleTestKernel::class;

        static::bootKernel(
            [
                'environment' => 'test',
                'debug'       => true,
            ]
        );

        $config           = array(
            'functions' => array(
                'testhello' => array(
                    'service' => 'wa72_jsonrpc.testservice',
                    'method'  => 'hello',
                ),
            ),
        );
        $this->controller = new JsonRpcController(static::$kernel->getContainer(), $config);

        $this->controllerFromContainer = static::$kernel->getContainer()->get('wa72_jsonrpc.jsonrpccontroller');
    }

    public function testHello()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'test',
            'method'  => 'testhello',
            'params'  => array('name' => 'Joe'),
        );
        $response    = $this->makeRequest($this->controller, $requestdata);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('test', $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertEquals('Hello Joe!', $response['result']);
    }

    public function testHelloMissingParameter()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'test',
            'method'  => 'testhello',
        );
        $response    = $this->makeRequest($this->controller, $requestdata);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('result', $response);
        $this->assertEquals(-32602, $response['error']['code']);
    }

    public function testService()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'testservice',
            'method'  => 'wa72_jsonrpc.testservice:hello',
            'params'  => array('name' => 'Max'),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('testservice', $response['id']);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('Hello Max!', $response['result']);
    }

    public function testServiceNonExistingServiceShouldReturnMethodNotFoundError()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'testservice',
            'method'  => 'someservice:somemethod',
            'params'  => array('name' => 'Max'),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals('testservice', $response['id']);
        $this->assertArrayNotHasKey('result', $response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals(-32601, $response['error']['code']);
    }

    public function testParametersAsAssocArrayInRightOrder()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'parametertest',
            'method'  => 'wa72_jsonrpc.testservice:parametertest',
            'params'  => array('arg1' => 'abc', 'arg2' => 'def', 'arg_array' => array()),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('abcdef', $response['result']);
    }

    public function testParametersSimpleArrayInRightOrder()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'parametertest',
            'method'  => 'wa72_jsonrpc.testservice:parametertest',
            'params'  => array('abc', 'def', array()),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('abcdef', $response['result']);
    }

    public function testParametersAsAssocArrayInMixedOrder()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'parametertest',
            'method'  => 'wa72_jsonrpc.testservice:parametertest',
            'params'  => array('arg_array' => array(), 'arg2' => 'def', 'arg1' => 'abc'),
        );

        $response = $this->makeRequest($this->controllerFromContainer, $requestdata);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('abcdef', $response['result']);
    }

    public function testAddMethod()
    {
        $requestdata = array(
            'jsonrpc' => '2.0',
            'id'      => 'test',
            'method'  => 'testhi',
            'params'  => array('name' => 'Tom'),
        );
        // this request will fail because there is no such method "testhi"
        $response = $this->makeRequest($this->controller, $requestdata);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('result', $response);
        $this->assertEquals(-32601, $response['error']['code']);

        // add the method definition for "testhi"
        $this->controller->addMethod('testhi', 'wa72_jsonrpc.testservice', 'hi');

        // now the request should succeed
        $response = $this->makeRequest($this->controller, $requestdata);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertEquals('Hi Tom!', $response['result']);
    }

    protected function makeRequest($controller, $requestdata)
    {
        return json_decode(
            $controller->execute(
                new Request(
                    array(),
                    array(),
                    array(),
                    array(),
                    array(),
                    array(),
                    json_encode($requestdata)
                )
            )->getContent(),
            true
        );
    }

}
