<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Filesystem\Resource;

use Puli\Filesystem\FilesystemException;
use Puli\Repository\ResourceNotFoundException;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\UnsupportedResourceException;
use Puli\Resource\DirectoryResource;
use Puli\Resource\DirectoryResourceInterface;
use Puli\Resource\ResourceInterface;

/**
 * Represents a directory on the local file system.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResource extends LocalResource implements DirectoryResourceInterface
{
    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    /**
     * {@inheritdoc}
     */
    public static function createAttached(ResourceRepositoryInterface $repo, $path, $localPath)
    {
        $resource = parent::createAttached($repo, $path, $localPath);
        $resource->repo = $repo;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($localPath)
    {
        parent::__construct($localPath);

        if (!is_dir($localPath)) {
            throw new FilesystemException(sprintf(
                'The path "%s" is not a directory.',
                $localPath
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        // Use attached locator if possible
        if ($this->repo) {
            return $this->repo->get($this->getPath().'/'.$name);
        }

        $localPath = $this->getLocalPath().'/'.$name;

        if (!file_exists($localPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" does not exist.',
                $localPath
            ));
        }

        return is_dir($localPath)
            ? new LocalDirectoryResource($localPath)
            : new LocalFileResource($localPath);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($name)
    {
        // Use attached locator if possible
        if ($this->repo) {
            return $this->repo->contains($this->getPath().'/'.$name);
        }

        return file_exists($this->getLocalPath().'/'.$name);
    }

    /**
     * {@inheritdoc}
     */
    public function listEntries()
    {
        // Use attached locator if possible
        if ($this->repo) {
            $entries = new LocalResourceCollection();

            foreach ($this->repo->find($this->getPath().'/*') as $entry) {
                $entries[$entry->getName()] = $entry;
            }

            return $entries;
        }

        $localPath = $this->getLocalPath();
        $entries = array();

        // We can't use glob() here, because glob() doesn't list files starting
        // with "." by default
        foreach (scandir($localPath) as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $entries[$name] = is_dir($localPath.'/'.$name)
                ? new LocalDirectoryResource($localPath.'/'.$name)
                : new LocalFileResource($localPath.'/'.$name);
        }

        return new LocalResourceCollection($entries);

    }

    /**
     * {@inheritdoc}
     */
    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        parent::attachTo($repo, $path);

        $this->repo = $repo;
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        parent::detach();

        $this->repo = null;
    }

    /**
     * {@inheritdoc}
     */
    public function override(ResourceInterface $resource)
    {
        // Virtual directories may be overridden
        if ($resource instanceof DirectoryResource) {
            return;
        }

        if (!($resource instanceof DirectoryResourceInterface && $resource instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local directory resources.');
        }

        parent::override($resource);
    }
}
