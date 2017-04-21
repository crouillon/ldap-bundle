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

use LpDigital\Bundle\LdapBundle\Security\Context\RestfulLdapContext;
use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;

/**
 * Test suite for RestfulLdapContext
 *
 * @copyright ©2017 - Lp digital
 * @author    Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers    LpDigital\Bundle\LdapBundle\Security\Context\RestfulLdapContext
 */
class RestfulLdapContextTest extends LdapTestCase
{
    /**
     * @var RestfulLdapContext
     */
    private $context;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $this->context = new RestfulLdapContext($this->bundle->getApplication()->getSecurityContext());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Context\RestfulLdapContext::loadListeners()
     */
    public function testLoadListeners()
    {
        $config = [
            'restful' => [
                'ldap' => [
                    'service' => 'bundle.ldap',
                    'dn_string' => '{username}'
                ],
                'provider' => 'bb_ldap'
            ]
        ];

        $this->assertEquals([], $this->context->loadListeners($config));
    }
}
