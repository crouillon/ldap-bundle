<?php

/*
 * Copyright (c) 2017 Lp digital system
 *
 * This file is part of ldap-bundle.
 *
 * ldap-bundle is free bundle: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ldap-bundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ldap-bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace LpDigital\Bundle\LdapBundle\Test;

use org\bovigo\vfs\vfsStream;

use BackBee\Tests\Mock\MockBBApplication;

use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * Test case for ldap-bundle.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Ldap
     */
    protected $bundle;

    /**
     * Sets up the required fixtures.
     */
    public function setUp()
    {
        parent::setUp();

        $mockConfig = [
            'ClassContent' => [],
            'Config' => [
                'bootstrap.yml' => file_get_contents(__DIR__ . '/Config/bootstrap.yml'),
                'config.yml' => file_get_contents(__DIR__ . '/Config/config.yml'),
                'services.yml' => file_get_contents(__DIR__ . '/Config/services.yml'),
                'security.yml' => file_get_contents(__DIR__ . '/Config/security.yml'),
            ],
            'cache' => [
                'container' => [],
            ],
            'log' => []
        ];

        vfsStream::umask(0000);
        vfsStream::setup('repositorydir', 0777, $mockConfig);

        $application = new MockBBApplication(null, null, false, $mockConfig, __DIR__ . '/../vendor');
        $this->bundle = $application->getBundle('ldap');
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object &$object    Instantiated object that we will run method on.
     * @param  string $methodName Method name to call.
     * @param  array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @link https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Returns protected/private property value of a class.
     *
     * @param  object $object       Instantiated object that we will run method on.
     * @param  string $propertyName Property name to return.
     * @param  mixed  $value        Optional, a value to be setted.
     *
     * @return mixed                Property value return.
     * @link https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     */
    public function invokeProperty($object, $propertyName, $value = null)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        if (null !== $value) {
            $property->setValue($object, 'null' === $value ? null : $value);
        }

        return $property->getValue($object);
    }
}
