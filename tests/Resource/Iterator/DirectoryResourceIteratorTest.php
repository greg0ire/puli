<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource\Iterator;

use Webmozart\Puli\Resource\Iterator\DirectoryResourceIterator;
use Webmozart\Puli\Tests\Resource\TestDirectory;
use Webmozart\Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultIteration()
    {
        $dir1 = new TestDirectory('/webmozart', array(
            $dir11 = new TestDirectory('/webmozart/puli', array(
                $dir111 = new TestDirectory('/webmozart/puli/config', array(
                    $file1111 = new TestFile('/webmozart/puli/config/config.yml'),
                    $file1112 = new TestFile('/webmozart/puli/config/routing.yml'),
                )),
                $dir112 = new TestDirectory('/webmozart/puli/css', array(
                    $file1121 = new TestFile('/webmozart/puli/css/style.css'),
                )),
                $file113 = new TestFile('/webmozart/puli/installer.json'),
            ))
        ));

        $iterator = new \RecursiveIteratorIterator(
            new DirectoryResourceIterator($dir1),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart/puli' => $dir11,
            '/webmozart/puli/config' => $dir111,
            '/webmozart/puli/config/config.yml' => $file1111,
            '/webmozart/puli/config/routing.yml' => $file1112,
            '/webmozart/puli/css' => $dir112,
            '/webmozart/puli/css/style.css' => $file1121,
            '/webmozart/puli/installer.json' => $file113,
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testCurrentAsPath()
    {
        $dir1 = new TestDirectory('/webmozart', array(
            $dir11 = new TestDirectory('/webmozart/puli', array(
                $dir111 = new TestDirectory('/webmozart/puli/config', array(
                    $file1111 = new TestFile('/webmozart/puli/config/config.yml'),
                    $file1112 = new TestFile('/webmozart/puli/config/routing.yml'),
                )),
                $dir112 = new TestDirectory('/webmozart/puli/css', array(
                    $file1121 = new TestFile('/webmozart/puli/css/style.css'),
                )),
                $file113 = new TestFile('/webmozart/puli/installer.json'),
            ))
        ));

        $iterator = new \RecursiveIteratorIterator(
            new DirectoryResourceIterator(
                $dir1,
                DirectoryResourceIterator::CURRENT_AS_PATH
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart/puli' => '/webmozart/puli',
            '/webmozart/puli/config' => '/webmozart/puli/config',
            '/webmozart/puli/config/config.yml' => '/webmozart/puli/config/config.yml',
            '/webmozart/puli/config/routing.yml' => '/webmozart/puli/config/routing.yml',
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
            '/webmozart/puli/installer.json' => '/webmozart/puli/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}