<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource\Iterator;

use Puli\Resource\Collection\ResourceCollectionInterface;
use Puli\Resource\DirectoryResourceInterface;
use Puli\Resource\ResourceInterface;

/**
 * A recursive iterator for resource collections.
 *
 * Use the iterator if you want to iterate a resource collection. You can
 * configure what the iterator should return as keys and values:
 *
 * ```php
 * $iterator = new ResourceCollectionIterator(
 *     $collection,
 *     ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_RESOURCE
 * );
 *
 * foreach ($iterator as $path => $resource) {
 *     // ...
 * }
 * ```
 *
 * If you want to iterate the collection recursively, wrap it in a
 * {@link RecursiveResourceIterator}:
 *
 * ```php
 * $iterator = new RecursiveResourceIterator(
 *     new ResourceCollectionIterator(
 *         $collection,
 *         ResourceCollectionIterator::KEY_AS_PATH | ResourceCollectionIterator::CURRENT_AS_RESOURCE
 *     ),
 *     RecursiveResourceIterator::SELF_FIRST
 * );
 *
 * foreach ($iterator as $path => $resource) {
 *     // ...
 * }
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollectionIterator implements RecursiveResourceIteratorInterface
{
    /**
     * Return {@link ResourceInterface} instances as values.
     */
    const CURRENT_AS_RESOURCE = 1;

    /**
     * Return the paths of the resources as values.
     */
    const CURRENT_AS_PATH = 2;

    /**
     * Return the names of the resources as values.
     */
    const CURRENT_AS_NAME = 4;

    /**
     * Return the paths of the resources as keys.
     */
    const KEY_AS_PATH = 64;

    /**
     * Return the collection keys as keys.
     *
     * Attention: Don't use this mode when iterating recursively, as PHP's
     * {@link \RecursiveIteratorIterator} skips inner nodes then.
     */
    const KEY_AS_CURSOR = 128;

    /**
     * @var ResourceInterface[]
     */
    protected $resources;

    /**
     * @var int
     */
    protected $mode;

    /**
     * Creates a new iterator.
     *
     * The following constants can be used to configure the values returned by
     * the iterator:
     *
     *  * {@link CURRENT_AS_RESOURCE}: The {@link ResourceInterface} objects are
     *                                 returned as values;
     *  * {@link CURRENT_AS_PATH}: The resource paths are returned as values;
     *  * {@link CURRENT_AS_NAME}: The resource names are returned as values.
     *
     * The following constants can be used to configure the keys returned by
     * the iterator:
     *
     *  * {@link KEY_AS_CURSOR}: The collection keys are returned as keys;
     *  * {@link KEY_AS_PATH}: The resource paths are returned as keys.
     *
     * By default, the mode `KEY_AS_PATH | CURRENT_AS_RESOURCE` is used.
     *
     * @param ResourceCollectionInterface $resources The resources to iterate.
     * @param int|null                    $mode      A bitwise combination of
     *                                               the mode constants.
     */
    public function __construct(ResourceCollectionInterface $resources, $mode = null)
    {
        if (!($mode & (self::CURRENT_AS_PATH | self::CURRENT_AS_RESOURCE | self::CURRENT_AS_NAME))) {
            $mode |= self::CURRENT_AS_RESOURCE;
        }

        if (!($mode & (self::KEY_AS_PATH | self::KEY_AS_CURSOR))) {
            $mode |= self::KEY_AS_PATH;
        }

        $this->resources = $resources->toArray();
        $this->mode = $mode;
    }

    /**
     * Returns the current value of the iterator.
     *
     * @return ResourceInterface|string The current value as configured in
     *                                  {@link __construct}.
     */
    public function current()
    {
        if ($this->mode & self::CURRENT_AS_RESOURCE) {
            return current($this->resources);
        }

        if ($this->mode & self::CURRENT_AS_PATH) {
            return current($this->resources)->getPath();
        }

        return current($this->resources)->getName();
    }

    /**
     * Advances the iterator to the next position.
     */
    public function next()
    {
        next($this->resources);
    }

    /**
     * Returns the current key of the iterator.
     *
     * @return integer|string|null The current key as configured in
     *                             {@link __construct) or `null` if the cursor
     *                             is behind the last element.
     */
    public function key()
    {
        if (null === ($key = key($this->resources))) {
            return null;
        }

        if ($this->mode & self::KEY_AS_PATH) {
            return $this->resources[$key]->getPath();
        }

        return $key;
    }

    /**
     * Returns whether the iterator points to a valid key.
     *
     * @return bool Whether the iterator position is valid.
     */
    public function valid()
    {
        return null !== key($this->resources);
    }

    /**
     * Rewinds the iterator to the first entry.
     */
    public function rewind()
    {
        reset($this->resources);
    }

    /**
     * Returns whether the iterator can be applied recursively over the
     * current element.
     *
     * @return bool Whether the current element can be iterated recursively.
     */
    public function hasChildren()
    {
        return current($this->resources) instanceof DirectoryResourceInterface;
    }

    /**
     * Returns the iterator for the children of the current element.
     *
     * @return static Returns an instance of this class for the children of
     *                the current element.
     */
    public function getChildren()
    {
        return new static(current($this->resources)->listEntries(), $this->mode);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentResource()
    {
        return current($this->resources);
    }
}
