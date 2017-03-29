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

namespace LpDigital\Bundle\LdapBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Security\LdapUserProvider;

/**
 * The authentication provider for LDAP users.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapAuthenticationProvider extends UserAuthenticationProvider
{

    /**
     * A LDAP user provider.
     *
     * @var LdapUserProvider
     */
    private $userProvider;

    /**
     * Constructor.
     *
     * @param LdapUserProvider     $userProvider               A LDAP user provider instance.
     * @param UserCheckerInterface $userChecker                An UserCheckerInterface instance.
     * @param string               $providerKey                The provider key.
     * @param bool                 $hideUserNotFoundExceptions Whether to hide user not found exception or not.
     */
    public function __construct(LdapUserProvider $userProvider, UserCheckerInterface $userChecker, $providerKey, $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);

        $this->userProvider = $userProvider;
    }

    /**
     * Does additional checks on the user and token (like validating the
     * credentials).
     *
     * @param  UserInterface         $user  The retrieved UserInterface instance
     * @param  UsernamePasswordToken $token The UsernamePasswordToken token to be authenticated
     *
     * @throws Exception\AuthenticationException if the credentials could not be validated
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $password = $token->getCredentials();

        if (empty($password)) {
            throw new Exception\BadCredentialsException('The presented password cannot be empty.');
        }

        if (!($user instanceof LdapUser) || null === $user->getEntry()) {
            throw new Exception\UnsupportedUserException('Unsupported user.');
        }

        if (null === $this->userProvider->getLdap()) {
            throw new Exception\AuthenticationServiceException('No LDAP adapter defined.');
        }

        try {
            $this->userProvider->getLdap()->bind($user->getEntry()->getDn(), $password);
        } catch (\Exception $ex) {
            throw new Exception\BadCredentialsException('The presented password is invalid.', 0, $ex);
        }
    }

    /**
     * Retrieves the user from an implementation-specific location.
     *
     * @param string                $username The username to retrieve
     * @param UsernamePasswordToken $token    The Token
     *
     * @return UserInterface The user
     *
     * @throws AuthenticationException if the credentials could not be validated
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        if ($token->getUser() instanceof LdapUser) {
            return $token->getUser();
        }

        try {
            return $this->userProvider->loadUserByUsername($username);
        } catch (Exception\UsernameNotFoundException $e) {
            $e->setUsername($username);
            throw $e;
        } catch (\Exception $e) {
            $e = new Exception\AuthenticationServiceException($e->getMessage(), 0, $e);
            $e->setToken($token);
            throw $e;
        }
    }
}
