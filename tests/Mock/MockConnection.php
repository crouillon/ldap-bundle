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

use Symfony\Component\Ldap\Adapter\ConnectionInterface;
use Symfony\Component\Ldap\Exception\LdapException;

/**
 * Mock object for LDAP Connection.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockConnection implements ConnectionInterface
{

    /**
     * @var bool
     */
    private $bound = false;

    /**
     * Binds the connection against a DN and password.
     *
     * @param string $dn       The user's DN
     * @param string $password The associated password
     */
    public function bind($dn = null, $password = null)
    {
        if ('good' === $dn && 'good' === $password) {
            $this->bound = true;
        } else {
            throw new LdapException('');
        }
    }

    /**
     * Checks whether the connection was already bound or not.
     *
     * @return bool
     */
    public function isBound()
    {
        return $this->bound;
    }
}
