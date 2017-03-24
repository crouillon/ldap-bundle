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

namespace LpDigital\Bundle\LdapBundle\Test\User;

use Doctrine\ORM\Tools\SchemaTool;

use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockLdap;
use LpDigital\Bundle\LdapBundle\User\LdapUser;
use LpDigital\Bundle\LdapBundle\User\LdapUserProvider;

/**
 * Test suite for LdapUserProvider
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers       LpDigital\Bundle\LdapBundle\User\LdapUserProvider
 */
class LdapUserProviderTest extends LdapTestCase
{

    /**
     * @var LdapUserProvider
     */
    private $provider;

    /**
     * @var MockLdap
     */
    private $mockLdap;

    /**
     * Sets up the required fixtures.
     */
    public function setUp()
    {
        parent::setUp();

        $em = $this->bundle->getEntityManager();
        $metadata = [
            $em->getClassMetadata(LdapUser::class),
        ];

        $schema = new SchemaTool($em);
        $schema->createSchema($metadata);

        $this->provider = $em->getRepository(LdapUser::class);
        $this->mockLdap = new MockLdap();
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::setLdap()
     */
    public function testSetLdap()
    {
        $this->assertEquals($this->provider, $this->provider->setLdap($this->mockLdap));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUserByUsername()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage The LDAP client is not defined.
     */
    public function testInvalidLoadUserByUsername()
    {
        $this->invokeProperty($this->provider, 'ldap', 'null');
        $this->provider->loadUserByUsername('username');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUserByUsername()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User `unknown` not found.
     */
    public function testUnknownLoadUserByUsername()
    {
        $this->provider
                ->setLdap($this->mockLdap)
                ->loadUserByUsername('unknown');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUserByUsername()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User `found` not found.
     */
    public function testNotPersistedLoadUserByUsername()
    {
        $this->provider
                ->setLdap($this->mockLdap)
                ->loadUserByUsername('found');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUserByUsername()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage More than one user found with `multiple`.
     */
    public function testMultipleLoadUserByUsername()
    {
        $this->provider
                ->setLdap($this->mockLdap)
                ->loadUserByUsername('multiple');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUserByUsername()
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::loadUser()
     */
    public function testLoadByUsername()
    {
        $user = $this->provider
                ->setLdap($this->mockLdap->setOption('persist_on_missing', true))
                ->loadUserByUsername('found');

        $this->assertInstanceOf(LdapUser::class, $user);
        $this->assertInstanceOf(LdapUser::class, $this->provider->find('found'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::refreshUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Invalid user class `BackBee\Security\User`.
     */
    public function testInvalidRefreshUser()
    {
        $this->provider->refreshUser(new User());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::refreshUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Invalid user `unknown`.
     */
    public function testUnknownRefreshUser()
    {
        $this->provider->refreshUser(new LdapUser('unknown'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::refreshUser()
     */
    public function testRefreshUser()
    {
        $user = $this->provider
                ->setLdap($this->mockLdap->setOption('persist_on_missing', true))
                ->loadUserByUsername('found');

        $this->assertEquals($user, $this->provider->refreshUser($user));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\User\LdapUserProvider::supportsClass()
     */
    public function testSupportClass()
    {
        $this->assertTrue($this->provider->supportsClass(LdapUser::class));
        $this->assertFalse($this->provider->supportsClass(User::class));
    }
}
