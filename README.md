JsonRpcBundle
=============

[![Build Status](https://secure.travis-ci.org/wasinger/jsonrpc-bundle.png?branch=master)](http://travis-ci.org/wasinger/jsonrpc-bundle)

JsonRpcBundle is a bundle for Symfony 2.1 and up that allows to easily build a JSON-RPC server for web services using [JSON-RPC 2.0] (http://www.jsonrpc.org/specification).

The bundle contains a controller that is able to expose methods of any service registered in the Symfony service container as a JSON-RPC web service. The return value of the service method is converted to JSON using [jms_serializer] (https://github.com/schmittjoh/JMSSerializerBundle), if this service is available, and json_encode() otherwise. 

Of course, it doesn't simply expose all your services' methods to the public, but only those explicitely mentioned in the configuration. And service methods cannot be called by it's original name but by an alias to be defined in the configuration.


Installation
------------

1. Add "wa72/jsonrpc-bundle" as requirement to your composer.json

2. Add "new Wa72\JsonRpcBundle\Wa72JsonRpcBundle()" in your AppKernel::registerBundles() function

3. Import the bundle's route in your routing.yml:

```yaml
# app/config/routing.yml
wa72_json_rpc:
    resource: "@Wa72JsonRpcBundle/Resources/config/routing.yml"
    prefix:   /jsonrpc
```

Your JSON-RPC web service will then be available in your project calling the /jsonrpc/ URL.

Configuration
-------------

You must configure which functions of the services registered in the Service Container will be available as web services.

Configuration is done under the "wa72_json_rpc" key of your configuration (usually defined in your app/config/config.yml).
To enable a Symfony2 service method to be called as a JSON-RPC web service, add it to the "functions" array of the configuration. 
The key of an entry of the "functions" array is the alias name for the method to be called over RPC and it needs two sub keys:
"service" specifies the name of the service and "method" the name of the method to call. Example:

```yaml
# app/config/config.yml
wa72_json_rpc:
    functions:
        myfunction1:
            service: "mybundle.servicename"
            method: "methodofservice"
        anotherfunction:
            service: "bundlename.foo"
            method: "bar"
```

In this example, "myfunction1" and "anotherfunction" are aliases for service methods that are used as JSON-RPC method names.
A method name "myfunction1" in the JSON-RPC call will then call the method "methodofservice" of service "mybundle.servicename".

Testing
-------

The bundle comes with a test service. To see if it works add the following configuration to your config.yml:

```yaml
# app/config/config.yml
wa72_json_rpc:
    functions:
        testhello:
            service: "wa72_jsonrpc.testservice"
            method: "hello"
```

If you have imported the bundle's routing to /jsonrpc (see above) you should then be able to test your service
by sending a JSON-RPC request using curl:

```bash
curl -XPost http://your-symfony-project/jsonrpc/ -d '{"jsonrpc":"2.0","method":"testhello","id":"foo","params":{"name":"Joe"}}'
```

and you should get the following answer:

```
{"jsonrpc":"2.0","result":"Hello Joe!","id":"foo"}
```

There are also unit tests you can run using phpunit.
