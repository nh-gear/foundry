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

namespace Gear\Foundry\Support\UnitTest\Mocks;


use Gear\Foundry\Actor;
use Gear\Foundry\EntityProviderInterface;

/**
 * Class TestEntityProvider
 * @package Gear\Foundry\Support\UnitTest\Mocks
 */
class TestEntityProvider implements EntityProviderInterface {

    /**
     * @param Actor $boot
     * @return bool
     */
    public function foo(Actor $boot)
    {
        return true;
    }

    /**
     * @param Actor $boot
     * @return bool
     */
    public function bar(Actor $boot)
    {
        return true;
    }

    /**
     * returns a entity map that assigns a callable ( value ) to a name ( key )
     * @param EntityProviderInterface $provider
     * @return array
     */
    public static function getEntityMap(EntityProviderInterface $provider)
    {
        return [
            'foo' => [$provider, 'foo'],
            'bar' => [$provider, 'bar'],
        ];
    }

    /**
     * returns a priority map that assigns a priority expression ( value ) to a name ( key )
     * @return mixed
     */
    public static function getPriorityMap()
    {
        return [
            'foo' => 0,
            'bar' => ['after' => 'foo'],
        ];
    }


} 