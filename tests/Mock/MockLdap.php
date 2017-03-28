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

namespace LpDigital\Bundle\LdapBundle\Test\Mock;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;

use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * Mock object for Ldap
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockLdap extends Ldap
{

    /**
     * Mock constructor.
     */
    public function __construct()
    {

    }

    /**
     * Checks a connection bound to the ldap.
     *
     * @param string $dn       A LDAP dn
     * @param string $password A password
     *
     * @throws LdapException if dn / password could not be bound.
     */
    public function bind($dn, $password)
    {
        if ('good' !== $dn || 'good' !== $password) {
            throw new LdapException();
        }
    }

    /**
     * Looks for LDAP entries matching $username.
     *
     * @param  string $username
     *
     * @return Entry[]
     */
    public function query($username)
    {
        $entries = [];

        if ('found' === $username) {
            $entries[] = new Entry('good');
        } elseif ('multiple' === $username) {
            $entries[] = new Entry('dn1 found');
            $entries[] = new Entry('dn2 found');
        }

        return $entries;
    }
}
