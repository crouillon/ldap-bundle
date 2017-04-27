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
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

use BackBee\ApplicationInterface;
use BackBee\Security\ApiUserProviderInterface;
use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * A LDAP user provider for BackBee REST firewall.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapBBUserProvider implements ApiUserProviderInterface
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
        try {
            $ldapUser = $this->ldapProvider->loadUserByUsername($username);
        } catch (\Exception $ex) {
            return $this->loadBBUserByUsername($username);
        }

        if (null === $ldapUser->getBbUser()) {
            if (true !== $this->getLdap()->persistOnMissing()) {
                return $this->loadBBUserByUsername($username);
            }

            $bbUser = new User();
            $bbUser->setLogin($username)
                    ->setPassword(md5(sha1($username. uniqid('', true))))
                    ->setEmail('')
                    ->setApiKeyEnabled(true)
                    ->setApiKeyPublic(md5($username))
                    ->setApiKeyPrivate(md5(sha1(microtime() * strlen($username))))
                    ->setActivated(true);

            if (null !== $cn = $ldapUser->getAttribute('cn')) {
                $bbUser->setLastname(is_array($cn) ? reset($cn) : $cn);
            }

            if (null !== $email = $ldapUser->getAttribute('mail')) {
                $bbUser->setEmail(is_array($email) ? reset($email) : $email);
            } else {
                $bbUser->setEmail('');
            }

            $ldapUser->setBbUser($bbUser);

            try {
                $this->entityMgr->persist($bbUser);
                $this->entityMgr->flush();
            } catch (\Exception $ex) {
                throw new \RuntimeException(sprintf('An error occured: %s', $ex->getMessage()));
            }
        }

        if (!$ldapUser->getBbUser()->isActivated()) {
            throw new DisabledException(sprintf('Account `%s` is disabled.', $ldapUser->getBbUser()->getUsername()));
        }

        return $ldapUser;
    }

    /**
     * Loads a BackBee user by his username.
     *
     * @param  string $username
     *
     * @return User
     */
    protected function loadBBUserByUsername($username)
    {
        $userProvider = $this->entityMgr->getRepository(User::class);

        return $userProvider->loadUserByUsername($username);
    }

    /**
     * Refreshes the user for the account interface.
     *
     * @param  UserInterface $user
     *
     * @return User
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Invalid user class `%s`.', get_class($user)));
        }

        return $user;
    }

    /**
     * Loads the user for the given public API key.
     *
     * @param  string $publicApiKey The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByPublicKey($publicApiKey)
    {
        $bbUser = $this->entityMgr
                    ->getRepository(User::class)
                    ->findOneBy(['_api_key_public' => $publicApiKey]);

        if (null === $bbUser) {
            throw new UsernameNotFoundException(sprintf('Unknown public API key `%s`.', $publicApiKey));
        }

        if (!$bbUser->isActivated()) {
            throw new DisabledException(sprintf('Account `%s` is disabled.', $bbUser->getUsername()));
        }

        return $bbUser;
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

    /**
     * Returns the current Ldap instance.
     *
     * @return Ldap|null
     *
     * @codeCoverageIgnore
     */
    public function getLdap()
    {
        return $this->ldapProvider->getLdap();
    }
}
