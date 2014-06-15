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
 * Foundry Container
 *
 * @package Gear\Foundry
 */
use Gear\Foundry\Exceptions\FatalQueueException;
use Gear\Foundry\Exceptions\QueueException;
use Gear\Foundry\Exceptions\NotFoundException;
use Interop\Container\ContainerInterface;

/**
 * Class Container
 * @package Gear\Foundry
 */
class Container implements ContainerInterface {

    /**
     * Callable registry
     * @var array
     */
    private $items = [];
    /**
     * Closure binding data
     * @var bool|array
     */
    private $closureBinding = false;

    /**
     * sets the given callable and priority to a name at the callable registry.
     * Existing entries will be overwritten without any notice.
     *
     * @param $name
     * @param callable $callable
     * @param null $priority
     * @throws \LogicException
     */
    public function attach($name, callable $callable, $priority = null)
    {
        if ( is_array($priority) ) {
            $priority = array_change_key_case($priority, CASE_LOWER);

            if ( count($priority) !== 1 ) {
                throw new \LogicException(
                    'Foundry container priorities may have a describing priority array with at least one '.
                    'entry, got '.count($priority).' entries.'
                );
            }

            if ( key($priority) !== 'before' && key($priority) !== 'after' ) {
                throw new \LogicException(
                    'Unknown priority direction: '.key($priority)
                );
            }
        }
        else {
            $priority = (int) $priority;
        }

        $this->items[$name] = [
            'callable' => $callable,
            'priority' => $priority,
        ];
    }

    /**
     * removes the given name from the Callable registry
     *
     * @param $name
     */
    public function detach($name)
    {
        unset($this->items[$name]);
    }

    /**
     * checks if a given name is stored at the callable registry.
     *
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }

    /**
     * fetches the callable from the container for a given name.
     *
     * @param $name
     * @return mixed
     * @throws NotFoundException
     */
    public function get($name)
    {
        if ( ! $this->has($name) ) {
            throw new NotFoundException('Unknown entity: '.$name);
        }

        return $this->items[$name]['callable'];
    }

    /**
     * sets the closure binding data for this container
     *
     * @param $object
     * @param $className
     * @param string $scope
     */
    public function setClosureBinding($object, $className, $scope = 'static')
    {
        $this->closureBinding = [
            'object' => $object,
            'class' => $className,
            'scope' => $scope,
        ];
    }

    /**
     * registers a given entity provider to the current container.
     *
     * @param EntityProviderInterface $provider
     * @throws \LogicException
     */
    public function register(EntityProviderInterface $provider)
    {
        $entityMap = $provider::getEntityMap($provider);
        $priorityMap = $provider::getPriorityMap();

        foreach ( $entityMap as $name => $current ) {
            if ( ! is_callable($current) ) {
                throw new \LogicException(
                    'entity map must be an array of callable entities'
                );
            }

            $arguments = [$name, $current];

            if ( array_key_exists($name, $priorityMap) ) {
                $arguments[] = $priorityMap[$name];
            }

            call_user_func_array([$this, 'attach'], $arguments);
        }
    }

    /**
     * executes the container a optional array may be passed to this method that keeps additional parameters
     * which will be passed after the actor as parameters to each entity call.
     *
     * @param array $executeArguments
     * @throws QueueException|FatalQueueException
     */
    public function execute(array $executeArguments = [])
    {
        $priorities = array_combine(array_keys($this->items),array_column($this->items, 'priority'));
        $priorityStack = new \SplQueue();
        $priorityStack->setIteratorMode(\SplQueue::IT_MODE_DELETE);
        array_map([$priorityStack, 'enqueue'], array_keys($priorities));

        foreach ( $priorityStack as $dequeuedItem ) {
            if ( is_int($priorities[$dequeuedItem]) ) {
                continue;
            }

            if ( ! array_key_exists(current($priorities[$dequeuedItem]), $priorities) ) {
                throw new QueueException(
                    sprintf(
                        'Foundry container item with name `%s` points to an unavailable container item with name `%s`',
                        $dequeuedItem,
                        current($priorities[$dequeuedItem])
                    )
                );
            }

            if ( current($priorities[$dequeuedItem]) === $dequeuedItem ) {
                throw new QueueException(
                    sprintf(
                        'Foundry container item with name `%s` points to `%s`, self-recursion detected',
                        $dequeuedItem,
                        current($priorities[$dequeuedItem])
                    )
                );
            }

            if ( is_array($priorities[current($priorities[$dequeuedItem])]) && current($priorities[current($priorities[$dequeuedItem])]) === $dequeuedItem ) {
                throw new QueueException(
                    sprintf(
                        'Foundry container item with name `%s` points to `%s` which points to `%s`, recursion detected',
                        $dequeuedItem,
                        current($priorities[$dequeuedItem]),
                        current($priorities[current($priorities[$dequeuedItem])])
                    )
                );
            }

            if ( ! is_int($priorities[current($priorities[$dequeuedItem])]) ) {
                $priorityStack->enqueue($dequeuedItem);
                continue;
            }

            if ( 'before' === key($priorities[$dequeuedItem]) ) {
                $priorities[$dequeuedItem] = $priorities[current($priorities[$dequeuedItem])] - 1;
            }
            else if ( 'after' === key($priorities[$dequeuedItem]) ) {
                $priorities[$dequeuedItem] = $priorities[current($priorities[$dequeuedItem])] + 1;
            }
        }

        asort($priorities);

        $priorityStack->setIteratorMode(\SplQueue::IT_MODE_KEEP);
        array_map([$priorityStack, 'enqueue'], array_keys($priorities));

        array_unshift($executeArguments, new Actor($priorityStack));

        foreach ( $priorityStack as $current ) {
            if ( false !== $this->closureBinding && $this->items[$current]['callable'] instanceof \Closure ) {
                $callable = call_user_func_array([$this->items[$current]['callable'],'bindTo'], $this->closureBinding);
            }
            else {
                $callable = $this->items[$current]['callable'];
            }

            try {
                call_user_func_array($callable, $executeArguments);
            }
            catch ( \LogicException $exception ) {
                throw new QueueException('Callable logic execution failed', 0, $exception);
            }
            catch ( \RuntimeException $exception ) {
                throw new FatalQueueException('Callable logic ends in fatal state', 500, $exception);
            }
        }
    }

} 