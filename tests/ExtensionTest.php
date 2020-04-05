<?php

namespace Bolt\Extension\Jadwigo\GeoApi\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\Jadwigo\GeoApi\GeoApiExtension;

/**
 * Ensure that the GeoApi extension loads correctly.
 *
 */
class ExtensionTest extends BoltUnitTest
{
    public function testExtensionRegister()
    {
        $app = $this->getApp();
        $extension = new Extension($app);
        $app['extensions']->register( $extension );
        $name = $extension->getName();
        $this->assertSame($name, 'GeoApi');
        $this->assertSame($extension, $app["extensions.$name"]);
    }
}
