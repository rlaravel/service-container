<?php

namespace Rlaravel\ServiceContainer\Test;

use PHPUnit\Framework\TestCase;
use Rlaravel\ServiceContainer\Container;
use Rlaravel\ServiceContainer\Exceptions\ContainerException;
use stdClass;

/**
 * Class ContainerTest
 * @package Rlaravel\ServiceContainer\Test
 */
class ContainerTest extends TestCase
{
    /**
     * @test
     */
    public function bind_from_closure()
    {
        $container = new Container();

        $container->bind('key', function () {
            return 'Object';
        });

        $this->assertSame('Object', $container->make('key'));
    }

    /**
     * @test
     */
    public function bind_instance()
    {
        $container = new Container();

        $stdClass = new StdClass();

        $container->instance('key', $stdClass);

        $this->assertSame($stdClass, $container->make('key'));
    }

    /**
     * @test
     */
    public function bind_from_class_name()
    {
        $container = new Container();

        $container->bind('key', StdClass::class);

        $this->assertInstanceOf(StdClass::class, $container->make('key'));
    }

    /**
     * @test
     */
    public function bind_with_automatic_resolution()
    {
        $container = new Container();

        $container->bind('foo', Foo::class);

        $this->assertInstanceOf(Foo::class, $container->make('foo'));
    }

    /**
     * @test
     */
    public function expected_container_exception_if_dependency_does_not_exist()
    {
        $this->expectException(ContainerException::class);

        $container = new Container();

        $container->bind('qux', Qux::class);

        $container->make('qux');
    }

    /**
     * @test
     */
    public function container_make_with_arguments()
    {
        $container = new Container();

        $this->assertInstanceOf(MailDummy::class, $container->make(MailDummy::class, [
            'url' => 'http://url.com',
            'key' => 'secret',
        ]));
    }
}

class MailDummy {

    protected $url;
    protected $key;

    public function __construct(string $url, string $key) {
        $this->url = $url;
        $this->key = $key;
    }
}

class Foo {
    public function __construct(Bar $bar, Baz $baz) {

    }
}

class Bar {
    public function __construct(FooBar $fooBar) {

    }
}

class FooBar {

}

class Baz {

}

class Qux {
    public function __construct(Nof $nof) {

    }
}