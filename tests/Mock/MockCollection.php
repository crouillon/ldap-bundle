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

use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Entry;

/**
 * Mock object for LDAP entries Collection
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockCollection implements CollectionInterface
{

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return 0 === $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return 0 === $offset ? $this->toArray()[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [new Entry('dn', ['cn' => 'Common Name'])];
    }
}
