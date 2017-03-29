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

namespace LpDigital\Bundle\LdapBundle\Test\Entity;

use Symfony\Component\Ldap\Entry;

use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;

/**
 * Test suite for LdapUser
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers       LpDigital\Bundle\LdapBundle\Entity\LdapUser
 */
class LdapUserTest extends \PHPUnit_Framework_TestCase
{

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

        $this->user = new LdapUser(
                'username', 'password', ['role1', 'role2'], new User()
        );
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getUsername()
     */
    public function testGetUsername()
    {
        $this->assertEquals('username', $this->user->getUsername());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getPassword()
     */
    public function testGetPassword()
    {
        $this->assertEquals('password', $this->user->getPassword());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::eraseCredentials()
     */
    public function testEraseCredentials()
    {
        $this->assertEquals($this->user, $this->user->eraseCredentials());
        $this->assertNull($this->user->getPassword());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getRoles()
     */
    public function testGetRoles()
    {
        $this->assertEquals(['role1', 'role2'], $this->user->getRoles());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getRoles()
     */
    public function testGetSalt()
    {
        $this->assertNull($this->user->getSalt());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getEntry()
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::setEntry()
     */
    public function testEntry()
    {
        $this->assertNull($this->user->getEntry());
        $this->assertEquals($this->user, $this->user->setEntry(new Entry('dn')));
        $this->assertInstanceOf(Entry::class, $this->user->getEntry());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getCreated()
     */
    public function testGetCreated()
    {
        $this->assertInstanceOf('DateTime', $this->user->getCreated());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Entity\LdapUser::getModified()
     */
    public function testGetModified()
    {
        $this->assertInstanceOf('DateTime', $this->user->getModified());
    }
}
