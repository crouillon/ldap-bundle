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

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider;
use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockLdap;

/**
 * Test suite for LdapBBUserProvider
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers       LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider
 */
class LdapBBUserProviderTest extends LdapTestCase
{

    /**
     * @var LdapBBUserProvider
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
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(LdapUser::class),
        ];

        $schema = new SchemaTool($em);
        $schema->createSchema($metadata);

        $this->provider = new LdapBBUserProvider($this->bundle->getApplication());

        $this->mockLdap = new MockLdap();
        $em->getRepository(LdapUser::class)->setLdap($this->mockLdap);

        $bbdisabled = new User('disabled');
        $bbdisabled->setEmail('email')->setApiKeyPublic('disabled_key');

        $bbuser = new User('user');
        $bbuser->setEmail('email')
            ->setActivated(true)
            ->setApiKeyPublic('user_key');

        $notInLdap = new User('notinldap');
        $notInLdap->setEmail('email')
            ->setActivated(true);

        $this->bundle->getEntityManager()->persist($bbdisabled);
        $this->bundle->getEntityManager()->persist($bbuser);
        $this->bundle->getEntityManager()->persist($notInLdap);

        $disabled = new LdapUser('disabled', 'disabled', 'disabled');
        $disabled->setBbUser($bbdisabled);

        $user = new LdapUser('good', 'found', 'good');

        $this->bundle->getEntityManager()->persist($disabled);
        $this->bundle->getEntityManager()->persist($user);

        $this->bundle->getEntityManager()->flush();
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByUsername()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage Unknown username `unknown`.
     */
    public function testUnknownLoadUserByUsername()
    {
        $this->provider->loadUserByUsername('unknown');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByUsername()
     * @expectedException        \Symfony\Component\Security\Core\Exception\DisabledException
     * @expectedExceptionMessage Account `disabled` is disabled.
     */
    public function testDisabledLoadUserByUsername()
    {
        $this->provider->loadUserByUsername('disabled');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByUsername()
     */
    public function testNotInLdapLoadUserByUsername()
    {
        $this->assertEquals('notinldap', $this->provider->loadUserByUsername('notinldap')->getUsername());

        $bbfound = new User('found');
        $bbfound->setEmail('email')->setActivated(true);
        $this->bundle->getEntityManager()->persist($bbfound);
        $this->bundle->getEntityManager()->flush($bbfound);

        $this->assertEquals('found', $this->provider->loadUserByUsername('found')->getUsername());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByUsername()
     */
    public function testNotPersistedLoadByUsername()
    {
        $this->mockLdap->setOption('persist_on_missing', true)->setOption('store_attributes', ['cn', 'mail']);
        $user = $this->provider->loadUserByUsername('found');

        $this->assertInstanceOf(User::class, $user->getBbUser());
        $this->assertEquals('found', $user->getBbUser()->getUsername());
        $this->assertEquals('mail@example.com', $user->getBbUser()->getEmail());
        $this->assertEquals('Common Name', $user->getBbUser()->getLastname());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByUsername()
     */
    public function testNotPersistedLoadByUsernameWithoutEmail()
    {
        $this->mockLdap->setOption('persist_on_missing', true)->setOption('store_attributes', ['cn']);
        $user = $this->provider->loadUserByUsername('found');

        $this->assertInstanceOf(User::class, $user->getBbUser());
        $this->assertEquals('found', $user->getBbUser()->getUsername());
        $this->assertEquals('', $user->getBbUser()->getEmail());
        $this->assertEquals('Common Name', $user->getBbUser()->getLastname());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::refreshUser()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Invalid user class `LpDigital\Bundle\LdapBundle\Entity\LdapUser`.
     */
    public function testInvalidRefreshUser()
    {
        $this->provider->refreshUser(new LdapUser('dn', 'username'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::refreshUser()
     */
    public function testRefreshUser()
    {
        $bbuser = $this->bundle->getEntityManager()->getRepository(User::class)->findOneBy(['_login' => 'user']);

        $this->assertEquals($bbuser, $this->provider->refreshUser($bbuser));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByPublicKey()
     * @expectedException        \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage Unknown public API key `unknown_key`.
     */
    public function testUnknownApiKey()
    {
        $this->provider->loadUserByPublicKey('unknown_key');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByPublicKey()
     * @expectedException        \Symfony\Component\Security\Core\Exception\DisabledException
     * @expectedExceptionMessage Account `disabled` is disabled.
     */
    public function testDisabledApiKey()
    {
        $this->provider->loadUserByPublicKey('disabled_key');
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::loadUserByPublicKey()
     */
    public function testLoadUserByPublicKey()
    {
        $bbuser = $this->bundle->getEntityManager()->getRepository(User::class)->findOneBy(['_login' => 'user']);

        $this->assertEquals($bbuser, $this->provider->loadUserByPublicKey('user_key'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\LdapBBUserProvider::supportsClass()
     */
    public function testSupportClass()
    {
        $this->assertTrue($this->provider->supportsClass(User::class));
        $this->assertFalse($this->provider->supportsClass(LdapUser::class));
    }
}
