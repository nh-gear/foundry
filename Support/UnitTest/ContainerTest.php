<?php
/**
 * Gear Component: Foundry
 *
 * Foundry is a object bootstrapping component build upon a queue mechanism.
 * Common use cases are: Application Bootstrapping, Container Bootstrapping, ...
 *
 * © 2014 concept and development by Matthias Kaschubowski
 *
 * Gear is a nihylum sub-namespace for standalone php components. Nihylum is a work namespace
 * of Matthias Kaschubowski.
 */

namespace Gear\Foundry\Support\UnitTest;

use Gear\Foundry\Container;
use Gear\Foundry\Actor;
use Gear\Foundry\Support\UnitTest\Mocks\TestEntityProvider;

class ContainerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function attachTest()
    {
        $container = new Container();

        $target = 'foo';
        $closure = function() {
            return true;
        };

        $container->attach($target, $closure);

        $this->assertEquals($closure, $container->get($target));
    }


    /**
     * @test
     */
    public function existTest()
    {
        $container = new Container();

        $target = 'foo';
        $closure = function() {
            return true;
        };

        $container->attach($target, $closure);

        $this->assertTrue($container->has($target));
    }

    /**
     * @test
     */
    public function detachTest()
    {
        $container = new Container();

        $target = 'foo';
        $closure = function() {
            return true;
        };

        $container->attach($target, $closure);

        $before = $container->has($target);

        $container->detach($target);

        $this->assertFalse($container->has($target));
        $this->assertTrue(! $container->has($target) && $before);
    }

    /**
     * @test
     */
    public function executeTest()
    {
        $container = new Container();

        $container->attach('foo', function() {
            echo 'success';
        });

        $this->expectOutputString('success');
        $container->execute();
    }

    /**
     * @test
     */
    public function executeAfterTest()
    {
        $container = new Container();

        $container->attach('foo', function() {
            echo 'success';
        });

        $container->attach('bar', function() {
            echo 'ful';
        }, ['after' => 'foo']);

        $this->expectOutputString('successful');
        $container->execute();
    }

    /**
     * @test
     */
    public function executeBeforeAndIgnoreTest()
    {
        $container = new Container();

        $container->attach('foo', function() {
            echo 'success';
        });

        $container->attach('bar', function(Actor $boot) {
            $boot->ignore('foo');
            echo 'successful';
        }, ['before' => 'foo']);

        $this->expectOutputString('successful');
        $container->execute();
    }

    /**
     * @test
     * @expectedException \Gear\Foundry\Exceptions\QueueException
     */
    public function executeSimpleRecursionTest()
    {
        $container = new Container();

        $container->attach('foo', function() {
            echo 'success';
        }, ['before' => 'bar']);

        $container->attach('bar', function(Actor $boot) {
            $boot->ignore('foo');
            echo 'successful';
        }, ['before' => 'foo']);

        $container->execute();
    }

    /**
     * @test
     * @expectedException \Gear\Foundry\Exceptions\QueueException
     */
    public function executeInExistingEntityTest()
    {
        $container = new Container();

        $container->attach('foo', function() {
            echo 'success';
        });

        $container->attach('bar', function(Actor $boot) {
            $boot->ignore('foo');
            echo 'successful';
        }, ['before' => 'baz']);

        $container->execute();
    }

    /**
     * @test
     */
    public function registerEntityProviderTest()
    {
        $container = new Container();

        $container->register(new TestEntityProvider());
        $container->execute();
    }
} 