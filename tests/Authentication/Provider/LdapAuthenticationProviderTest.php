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

namespace LpDigital\Bundle\LdapBundle\Test\Authentication\Provider;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserChecker;

use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider;
use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Security\LdapUserProvider;
use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockLdap;

/**
 * Test suite for LdapAuthentificationProvider
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider
 */
class LdapAuthenticationProviderTest extends LdapTestCase
{

    /**
     * @var LdapUserProvider
     */
    private $userProvider;

    /**
     * @var LdapAuthenticationProvider
     */
    private $provider;

    /**
     * Sets up the fixture.
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

        $this->userProvider = $this->bundle->getEntityManager()->getRepository(LdapUser::class);

        $this->provider = new LdapAuthenticationProvider(
                $this->userProvider, new UserChecker(), 'providerkey'
        );
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password cannot be empty.
     */
    public function testEmptyPassword()
    {
        $token = new UsernamePasswordToken('', '', 'providerkey');
        $this->invokeMethod($this->provider, 'checkAuthentication', [new LdapUser('username'), $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Unsupported user.
     */
    public function testUnsupportedUser()
    {
        $token = new UsernamePasswordToken('', 'password', 'providerkey');
        $this->invokeMethod($this->provider, 'checkAuthentication', [new User(), $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Unsupported user.
     */
    public function testIncompleteUser()
    {
        $this->userProvider->setLdap(new MockLdap());
        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->invokeMethod($this->provider, 'checkAuthentication', [new LdapUser('good'), $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\AuthenticationServiceException
     * @expectedExceptionMessage No LDAP adapter defined.
     */
    public function testUndefinedLdap()
    {
        $user = new LdapUser('good');
        $user->setEntry(new Entry('good'));

        $userProvider = $this->invokeProperty($this->provider, 'userProvider');
        $this->invokeProperty($userProvider, 'ldap', 'null');

        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password is invalid.
     */
    public function testInvalidPassword()
    {
        $user = new LdapUser('good');
        $user->setEntry(new Entry('good'));

        $this->userProvider->setLdap(new MockLdap());
        $token = new UsernamePasswordToken('good', 'bad', 'providerkey');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::checkAuthentication()
     */
    public function testCheckAuthentication()
    {
        $user = new LdapUser('good');
        $user->setEntry(new Entry('good'));

        $this->userProvider->setLdap(new MockLdap());
        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->assertNull($this->invokeMethod($this->provider, 'checkAuthentication', [$user, $token]));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::retrieveUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRetrieveInvalidUser()
    {
        $this->userProvider->setLdap(new MockLdap());
        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->invokeMethod($this->provider, 'retrieveUser', ['unknown', $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::retrieveUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\AuthenticationServiceException
     */
    public function testRetrieveInvalidLdap()
    {
        $userProvider = $this->invokeProperty($this->provider, 'userProvider');
        $this->invokeProperty($userProvider, 'ldap', 'null');

        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->invokeMethod($this->provider, 'retrieveUser', ['good', $token]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider::retrieveUser()
     */
    public function testRetrieveUser()
    {
        $mockLdap = new MockLdap();
        $this->userProvider->setLdap($mockLdap->setOption('persist_on_missing', true));
        $token = new UsernamePasswordToken('good', 'good', 'providerkey');
        $this->assertInstanceOf(LdapUser::class, $this->invokeMethod($this->provider, 'retrieveUser', ['found', $token]));

        $user = new LdapUser('good');
        $token->setUser($user);
        $this->assertEquals($user, $this->invokeMethod($this->provider, 'retrieveUser', ['good', $token]));
    }
}
