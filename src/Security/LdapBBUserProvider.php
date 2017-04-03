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

namespace LpDigital\Bundle\LdapBundle\Security;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\ApplicationInterface;
use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * A LDAP user provider for BackBee REST firewall.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapBBUserProvider implements UserProviderInterface
{

    /**
     * The LDAP bundle.
     *
     * @var Ldap
     */
    private $ldap;

    /**
     * The current entity manageR.
     *
     * @var EntityManager 
     */
    private $entityMgr;

    /**
     * The LDAP user provider.
     *
     * @var LdapUserProvider 
     */
    private $ldapProvider;

    /**
     * Provider constructor.
     * 
     * @param ApplicationInterface $bbapp
     */
    public function __construct(ApplicationInterface $bbapp)
    {
        $this->ldap = $bbapp->getBundle('ldap');
        $this->entityMgr = $bbapp->getEntityManager();

        $this->ldapProvider = $this->entityMgr->getRepository(LdapUser::class);
        $this->ldapProvider->setLdap($this->ldap);
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
        $ldapUser = $this->ldapProvider->loadUserByUsername($username);

        if (null === $ldapUser->getBbUser()) {
            if (true !== $this->ldap->getOption('persist_on_missing')) {
                throw new UsernameNotFoundException(sprintf('User `%s` not found.', $username));
            }

            $bbUser = new User(
                $username,
                md5(sha1($username. uniqid('', true))),
                $ldapUser->getAttribute('cn')
            );

            $bbUser->setApiKeyEnabled(true)
                    ->setApiKeyPublic(md5($username))
                    ->setApiKeyPrivate(md5(sha1(microtime() * strlen($username))))
                    ->setEmail($ldapUser->getAttribute('mail'))
                    ->setActivated(true);

            $ldapUser->setBbUser($bbUser);

            $this->entityMgr->persist($bbUser);
            $this->entityMgr->flush();
        }

        return $ldapUser->getBbUser();
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

        if (null === $ldapUser = $this->ldapProvider->findOneBy(['bbUser' => $user])) {
            throw new UnsupportedUserException(sprintf('Invalid user `%s`.', $user->getUsername()));
        }

        return $ldapUser->getBbUser();
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
        return User::class === $class;
    }
}
