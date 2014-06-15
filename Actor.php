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


class Actor {

    private $queue;

    public function __construct(\SplQueue $queue)
    {
        $this->queue = $queue;
        $this->items = new \ArrayObject(iterator_to_array($queue));
    }

    public function ignore($entity)
    {
        if ( false === $keys = array_keys($this->items->getArrayCopy(), $entity) ) {
            throw new \LogicException(
                sprintf(
                    'Can not ignore container item with name `%s`, item does not exists',
                    $entity
                )
            );
        }

        array_map([$this->queue, 'offsetUnset'], $keys);
        array_map([$this->items, 'offsetUnset'], $keys);
    }

    public function having($entity)
    {
        return false !== array_keys($this->items->getArrayCopy(), $entity);
    }
} 