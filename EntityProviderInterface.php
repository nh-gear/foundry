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

namespace Gear\Foundry;


/**
 * Interface EntityProviderInterface
 * @package Gear\Foundry
 */
interface EntityProviderInterface {
    /**
     * returns a entity map that assigns a callable ( value ) to a name ( key )
     * @param EntityProviderInterface $provider
     * @return array
     */
    public static function getEntityMap(EntityProviderInterface $provider);

    /**
     * returns a priority map that assigns a priority expression ( value ) to a name ( key )
     * @return mixed
     */
    public static function getPriorityMap();
} 