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
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider;
use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider;
use LpDigital\Bundle\LdapBundle\Security\LdapUserProvider;
use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockLdap;

/**
 * Test suite for LdapBBAuthentificationProvider
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider
 */
class LdapBBAuthenticationProviderTest extends LdapTestCase
{

    /**
     * @var LdapUserProvider
     */
    private $userProvider;

    /**
     * @var LdapBBAuthenticationProvider
     */
    private $provider;

    /**
     * @var LdapUser
     */
    private $user;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $em = $this->bundle->getEntityManager();
        $metadata = [
            $em->getClassMetadata(LdapUser::class),
            $em->getClassMetadata(User::class),
        ];

        $schema = new SchemaTool($em);
        $schema->createSchema($metadata);

        
        $this->userProvider = new LdapBBUserProvider($this->bundle->getApplication());
        $this->bundle->getEntityManager()->getRepository(LdapUser::class)->setLdap(new MockLdap());

        $this->provider = new LdapBBAuthenticationProvider(
            $this->userProvider,
            $this->bundle->getApplication(),
            vfsStream::url('repositorydir') . '/cache'
        );

        $bbdisabled = new User('disabled');
        $bbdisabled->setEmail('email');

        $bbuser = new User('user');
        $bbuser->setEmail('email')
            ->setActivated(true);

        $this->bundle->getEntityManager()->persist($bbdisabled);
        $this->bundle->getEntityManager()->persist($bbuser);

        $disabled = new LdapUser('disabled', 'disabled', 'disabled');
        $disabled->setBbUser($bbdisabled);

        $this->user = new LdapUser('good', 'found', 'good');
        $this->user->setBbUser($bbuser);

        $this->bundle->getEntityManager()->persist($disabled);
        $this->bundle->getEntityManager()->persist($this->user);

        $this->bundle->getEntityManager()->flush();
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::authenticate()
     */
    public function testUnsupportedToken()
    {
        $this->assertNull($this->provider->authenticate(new AnonymousToken('key', 'user')));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::retrieveUser()
     */
    public function testRetrieveAlreadyUser()
    {
        $user = new User();
        $token = new BBUserToken();
        $token->setUser($user);

        $this->assertEquals($user, $this->invokeMethod($this->provider, 'retrieveUser', ['username', $token]));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::retrieveUser()
     */
    public function testRetrieveUser()
    {
        $this->assertEquals(
            $this->user,
            $this->invokeMethod($this->provider, 'retrieveUser', ['found', new BBUserToken()])
        );
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::retrieveUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage Unknown username `unknown`.
     */
    public function testRetrieveUnknownUsername()
    {
        $this->invokeMethod($this->provider, 'retrieveUser', ['unknown', new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::retrieveUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\AuthenticationServiceException
     * @expectedExceptionMessage Account `disabled` is disabled.
     */
    public function testRetrieveDisabledUsername()
    {
        $this->invokeMethod($this->provider, 'retrieveUser', ['disabled', new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password cannot be empty.
     */
    public function testEmptyPassword()
    {
        $this->invokeMethod($this->provider, 'checkAuthentication', [new User(), new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Unsupported user.
     */
    public function testUnsupportedUser()
    {
        $this->bundle->getApplication()->getRequest()->request->set('password', 'password');
        $this->invokeMethod($this->provider, 'checkAuthentication', [new User(), new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\AuthenticationServiceException
     * @expectedExceptionMessage No LDAP adapter defined.
     */
    public function testUndefinedLdap()
    {
        $this->bundle->getApplication()->getRequest()->request->set('password', 'password');
        $this->invokeProperty($this->bundle->getEntityManager()->getRepository(LdapUser::class), 'ldap', 'null');

        $this->invokeMethod($this->provider, 'checkAuthentication', [$this->user, new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::checkAuthentication()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password is invalid.
     */
    public function testInvalidPassword()
    {
        $this->bundle->getApplication()->getRequest()->request->set('password', 'bad');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$this->user, new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::checkAuthentication()
     */
    public function testCheckAuthentication()
    {
        $this->bundle->getApplication()->getRequest()->request->set('password', 'good');
        $this->invokeMethod($this->provider, 'checkAuthentication', [$this->user, new BBUserToken()]);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::authenticate()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage Invalid connection information.
     */
    public function testEmptyUsername()
    {
        $token = new BBUserToken();
        $token->setUser('');
        $this->bundle->getApplication()->getRequest()->request->set('password', 'good');

        $this->provider->authenticate($token);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider::authenticate()
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage Invalid connection information.
     */
    public function testInvalidNonce()
    {
        $token = new BBUserToken();
        $token->setUser($this->user->getUsername());
        $this->bundle->getApplication()->getRequest()->request->set('password', 'good');

        $this->provider->authenticate($token);
    }

    public function testAuthenticate()
    {
        $token = new BBUserToken();

        $created = date('Y-m-d H:i:s');
        $token->setUser($this->user->getUsername());
        $token->setCreated($created);
        $token->setNonce(md5(uniqid('', true)));
        $token->setDigest(md5($token->getNonce().$created.md5('good')));

        $this->bundle->getApplication()->getRequest()->request->set('password', 'good');

        $this->assertInstanceOf(BBUserToken::class, $this->provider->authenticate($token));
    }
}
