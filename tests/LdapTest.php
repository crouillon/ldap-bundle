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

/**
 * Test suite for Ldap bundle class.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers       LpDigital\Bundle\LdapBundle\Ldap
 */
class LdapTest extends LdapTestCase
{

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();
        $this->bundle->start();
    }

    /**
     * @covers            LpDigital\Bundle\LdapBundle\Ldap::bind()
     * @expectedException \Symfony\Component\Ldap\Exception\LdapException
     */
    public function testInvalidBind()
    {
        $this->bundle->bind('notgood', 'notgood');
    }

    /**
     * @covers            LpDigital\Bundle\LdapBundle\Ldap::query()
     * @expectedException \Symfony\Component\Ldap\Exception\LdapException
     */
    public function testInvalidBindQuery()
    {
        $this->bundle->query('uername');
    }

    /**
     * @covers            LpDigital\Bundle\LdapBundle\Ldap::query()
     */
    public function testQuery()
    {
        $this->invokeProperty($this->bundle, 'options', [
            'base_dn' => '',
            'search_dn' => 'good',
            'search_password' => 'good',
            'filter' => '(sAMAccountName={username})',
            'persist_on_missing' => false,
        ]);

        $this->assertTrue(is_array($this->bundle->query('username')));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::getOption()
     */
    public function testGetOption()
    {
        $this->assertNull($this->bundle->getOption('inknown'));
        $this->assertEquals('(sAMAccountName={username})', $this->bundle->getOption('filter'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::start()
     */
    public function testStart()
    {
        $expectedOptions = [
            'base_dn' => '',
            'search_dn' => '',
            'search_password' => '',
            'filter' => '(sAMAccountName={username})',
            'persist_on_missing' => false,
        ];

        $this->assertEquals($expectedOptions, $this->invokeProperty($this->bundle, 'options'));
    }
}
