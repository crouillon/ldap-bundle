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

namespace LpDigital\Bundle\LdapBundle\Test\Security\Context;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

use BackBee\Security\Authentication\AuthenticationManager;
use BackBee\Security\SecurityContext;
use BackBee\Tests\Mock\MockBBApplication;

use LpDigital\Bundle\LdapBundle\Security\Context\LdapContext;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockInvalidFailureHandler;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockInvalidSuccessHandler;

/**
 * Test suite for LdapContext
 *
 * @copyright ©2017 - Lp digital
 * @author    Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers    LpDigital\Bundle\LdapBundle\Security\Context\LdapContext
 */
class LdapContextTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var LdapContext
     */
    private $context;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $mockConfig = [
            'ClassContent' => [],
            'Config' => [
                'bundle' => [
                    'ldap' => [
                        'services.yml' => file_get_contents(__DIR__ . '/../../Config/bundle/ldap/services.yml')
                    ]
                ],
                'bootstrap.yml' => file_get_contents(__DIR__ . '/../../Config/bootstrap.yml'),
                'config.yml' => file_get_contents(__DIR__ . '/../../Config/config.yml'),
                'services.yml' => file_get_contents(__DIR__ . '/../../Config/services.yml'),
                'security.yml' => '{"providers":{"ldap":{"entity":{"class":"LpDigital\\\\Bundle\\\\LdapBundle\\\\Entity\\\\LdapUser"}}}}'
            ],
            'cache' => [
                'container' => [],
            ],
            'log' => []
        ];

        vfsStream::umask(0000);
        vfsStream::setup('repositorydir', 0777, $mockConfig);

        $application = new MockBBApplication(null, null, false, $mockConfig, __DIR__ . '/../../../vendor');
        $authenticationManager = new AuthenticationManager([]);
        $accessDecisionManager = new AccessDecisionManager([new RoleVoter()]);
        $securityContext = new SecurityContext($application, $authenticationManager, $accessDecisionManager);

        $this->context = new LdapContext($securityContext);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::loadListeners()
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::getLdapServiceId()
     */
    public function testInvalidConfig()
    {
        $this->assertEquals([], $this->context->loadListeners([]));
        $this->assertEquals([], $this->context->loadListeners(['form_login_ldap' => ['service' => 'unknown.service']]));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::loadListeners()
     */
    public function testValidConfig()
    {
        $config = [
            'provider' => 'ldap',
            'form_login_ldap' => [
                'service' => 'bundle.mockldap',
                'dn_string' => '{username}'
            ]
        ];

        $this->assertEquals(1, count($this->context->loadListeners($config)));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::loadListeners()
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::createSuccessHandler()
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\LdapContext::createFailureHandler()
     */
    public function testCustomHandlers()
    {
        $config = [
            'provider' => 'ldap',
            'form_login_ldap' => [
                'service' => 'bundle.mockldap',
                'dn_string' => '{username}',
                'success_handler' => 'success.handler',
                'failure_handler' => 'failure.handler',
            ]
        ];

        $listeners = $this->context->loadListeners($config);
        $listener = $listeners[0];

        $this->assertInstanceOf(MockInvalidSuccessHandler::class, $this->invokeProperty($listener, 'successHandler'));
        $this->assertInstanceOf(MockInvalidFailureHandler::class, $this->invokeProperty($listener, 'failureHandler'));
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
