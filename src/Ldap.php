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

use BackBee\ApplicationInterface;
use BackBee\Bundle\AbstractBundle;
use BackBee\Config\Config;
use BackBee\Security\Group;
use BackBee\Utils\Collection\Collection;

/**
 * Ldap bundle entry point.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class Ldap extends AbstractBundle
{

    /**
     * Default options.
     *
     * @var array
     */
    private $defaultOptions = [
        'base_dn' => '',
        'search_dn' => '',
        'search_password' => '',
        'filter' => '(sAMAccountName={username})',
        'persist_on_missing' => false,
    ];

    /**
     * Current bundle options.
     *
     * @var string[]
     */
    protected $options;

    /**
     * Is the LDAP user persisted if he's missing in BackBee?
     *
     * @var boolean
     */
    protected $persistOnMissing;

    /**
     * Array of LDAP attributes to store.
     *
     * @var string[]
     */
    protected $storedAttributes;

    /**
     * Array of default BackBee groups for new user.
     *
     * @var Group[]
     */
    protected $defaultBackBeeGroups;

    /**
     * An LDAP clients collection.
     *
     * @var LdapClient[]
     */
    protected $ldapClients;

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
        $lastException = null;

        foreach ($this->getLdapClients() as $ldapClient) {
            try {
                $ldapClient->bind($dn, $password);
            } catch (\Exeption $ex) {
                $lastException = $ex;
            }

            return;
        }

        throw $lastException;
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
        $results = [];
        $lastException = null;

        foreach ($this->getLdapClients() as $name => $ldapClient) {
            $ldapClient->bind(
                $this->getOption($name, 'search_dn'),
                $this->getOption($name, 'search_password')
            );

            $query = str_replace(
                '{username}',
                $ldapClient->escape($username, '', LDAP_ESCAPE_FILTER),
                $this->getOption($name, 'filter')
            );

            try {
                $results = $ldapClient
                    ->query($this->getOption($name, 'base_dn'), $query)
                    ->execute();
            } catch (\Exception $ex) {
                $results = [];
                $lastException = $ex;
            }

            if ($results instanceof CollectionInterface && 0 < count($results)) {
                return $results->toArray();
            }
        }

        if (null !== $lastException) {
            throw $lastException;
        }

        return $results;
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
    public function getOption($server, $name, $default = null)
    {
        if (!isset($this->options[$server])) {
            return $default;
        }

        return isset($this->options[$server][$name]) ? $this->options[$server][$name] : $default;
    }

    /**
     * Returns the LDAP clients collection.
     *
     * @return LdapClient[]
     */
    protected function getLdapClients()
    {
        if (empty($this->ldapClients)) {
            foreach ((array) $this->getConfig()->getLdapConfig() as $name => $server) {
                $adapter = Collection::get($server, 'adapter', 'ext_ldap');
                $options = Collection::get($server, 'options', []);

                $this->ldapClients[$name] = LdapClient::create($adapter, $options);
                $this->options[$name] = array_merge(
                    $this->defaultOptions,
                    $server
                );
            }
        }

        return $this->ldapClients;
    }

    /**
     * Is the LDAP user persisted if he's missing in BackBee?
     *
     * @return boolean
     */
    public function persistOnMissing()
    {
        return true === Collection::get($this->getConfig()->getParametersConfig(), 'persist_on_missing', false);
    }

    /**
     * Array of LDAP attributes to store.
     *
     * @return string[]
     */
    public function getStoredAttributes()
    {
        return (array) Collection::get($this->getConfig()->getParametersConfig(), 'store_attributes', []);
    }

    /**
     * Array of default BackBee groups for new user.
     *
     * @var Group[]
     */
    public function getDefaultBackBeeGroups()
    {
        if (null === $this->defaultBackBeeGroups) {
            $defaultGroups = (array) Collection::get($this->getConfig()->getParametersConfig(), 'default_backbee_groups', []);
            foreach ($defaultGroups as $defaultGroup) {
                if (null === $group = $this->getEntityManager()->find(Group::class, $defaultGroup)) {
                    $group = $this->getEntityManager()->getRepository(Group::class)->findOneBy(['_name' => $defaultGroup]);
                }

                if (null !== $group) {
                    $this->defaultBackBeeGroups[$group->getId()] = $group;
                }
            }
        }

        return $this->defaultBackBeeGroups;
    }

    /**
     * Method to call when we get the bundle for the first time.
     */
    public function start()
    {
    }

    /**
     * Method to call before stop or destroy of current bundle.
     *
     * @codeCoverageIgnore
     */
    public function stop()
    {
    }

    /**
     * Adds the bunde views folder to the script directories of the BackBee renderer.
     *
     * @param ApplicationInterface $application
     * @param Config               $config
     */
    public static function loadViews(ApplicationInterface $application, Config $config)
    {
        if ($application->getContainer()->has('renderer')) {
            $renderer = $application->getContainer()->get('renderer');
            $renderer->addScriptDir(realpath(__DIR__ . '/../views'));
        }
    }

}
