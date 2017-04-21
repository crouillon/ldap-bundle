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

namespace LpDigital\Bundle\LdapBundle;

use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap as LdapClient;

use BackBee\Bundle\AbstractBundle;
use BackBee\DependencyInjection\Container;

/**
 * Ldap bundle entry point.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Ldap extends AbstractBundle
{

    /**
     * The current BackBee services container.
     *
     * @var Container
     */
    private $container;

    /**
     * Current bundle options.
     *
     * @var string[]
     */
    protected $options;

    /**
     * A LDAP client.
     *
     * @var LdapClient
     */
    protected $ldapClient;

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
        $this->getLdapClient()->bind($dn, $password);
    }

    /**
     * Looks for LDAP entries matching $username.
     *
     * @param  string  $username The username to look for.
     *
     * @return Entry[]           The matching LDAP entries.
     *
     * @throws \RuntimeException if something went wrong.
     */
    public function query($username)
    {
        $this->getLdapClient()->bind(
            $this->getOption('search_dn'),
            $this->getOption('search_password')
        );

        $query = str_replace(
            '{username}',
            $this->getLdapClient()->escape($username, '', LDAP_ESCAPE_FILTER),
            $this->getOption('filter')
        );

        $results = $this
            ->getLdapClient()
            ->query($this->getOption('base_dn'), $query)
            ->execute();

        return $results instanceof CollectionInterface ? $results->toArray() : $results;
    }

    /**
     * Sets an option value.
     *
     * @param  string $name  The option name.
     * @param  mixed  $value
     *
     * @return Ldap
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Returns an option value if exists, null elsewhere.
     *
     * @param  string $name    The option name.
     * @param  mixed  $default Optional, the default value to return.
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Returns the LDAP client service.
     *
     * @return LdapClient
     */
    protected function getLdapClient()
    {
        if (null === $this->ldapClient && $this->container->has('bundle.ldap.client')) {
            $this->ldapClient = $this->container->get('bundle.ldap.client');
        }

        return $this->ldapClient;
    }

    /**
     * Method to call when we get the bundle for the first time.
     */
    public function start()
    {
        $this->container = $this->getApplication()->getContainer();

        $defaultOptions = [
            'base_dn' => '',
            'search_dn' => '',
            'search_password' => '',
            'filter' => '(sAMAccountName={username})',
            'persist_on_missing' => false,
        ];

        $this->options = array_merge(
            $defaultOptions,
            $this->getConfig()->getLdapConfig()
        );
    }

    /**
     * Method to call before stop or destroy of current bundle.
     *
     * @codeCoverageIgnore
     */
    public function stop()
    {
    }
}
