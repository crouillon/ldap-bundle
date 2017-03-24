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

namespace LpDigital\Bundle\LdapBundle\User;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * A LDAP user provider.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapUserProvider extends EntityRepository implements UserProviderInterface
{

    /**
     * The LDAP bundle.
     *
     * @var Ldap
     */
    private $ldap;

    /**
     * Sets the LDAP bundle.
     *
     * @param  Ldap $ldap
     *
     * @return $this
     */
    public function setLdap(Ldap $ldap)
    {
        $this->ldap = $ldap;

        return $this;
    }

    /**
     * Loads the user for the given username.
     *
     * @param  string $username The username
     *
     * @return UserInterface
     *
     * @throws \RuntimeException         if something went wrong with LDAP connection.
     * @throws UsernameNotFoundException if the user is not found.
     */
    public function loadUserByUsername($username)
    {
        if (null === $this->ldap) {
            throw new \RuntimeException('The LDAP client is not defined.');
        }

        $entries = $this->ldap->query($username);
        if (0 === count($entries)) {
            throw new UsernameNotFoundException(sprintf('User `%s` not found.', $username));
        } elseif (1 < count($entries)) {
            throw new UsernameNotFoundException(sprintf('More than one user found with `%s`.', $username));
        }

        return $this->loadUser($username, reset($entries));
    }

    /**
     * Refreshes the user for the account interface.
     *
     * @param  UserInterface $user
     *
     * @return LdapUser
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Invalid user class `%s`.', get_class($user)));
        }

        if (null === $refreshed = $this->find($user->getUsername())) {
            throw new UnsupportedUserException(sprintf('Invalid user `%s`.', $user->getUsername()));
        }

        return $refreshed;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param  string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return LdapUser::class === $class;
    }

    /**
     * Returns a LdapUser entity, persist it if need.
     *
     * @param  string $username
     * @param  Entry  $entry
     *
     * @return LdapUser
     *
     * @throws UsernameNotFoundException if the user is not found.
     */
    private function loadUser($username, Entry $entry)
    {
        if (null === $user = $this->find($username)) {
            if (true !== $this->ldap->getOption('persist_on_missing')) {
                throw new UsernameNotFoundException(sprintf('User `%s` not found.', $username));
            }

            $user = new LdapUser($username);
            $user->setEntry($entry);

            $this->getEntityManager()->persist($user);
        }

        return $user;
    }
}
