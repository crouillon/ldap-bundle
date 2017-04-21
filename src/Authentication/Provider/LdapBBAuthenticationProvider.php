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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;

/**
 * Description of LdapBBAuthenticationProvider
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapBBAuthenticationProvider extends BBAuthenticationProvider
{

    private $bbapp;

    /**
     * Class constructor.
     *
     * @param UserProviderInterface $userProvider
     * @param string                                                      $nonceDir
     * @param int                                                         $lifetime
     * @param \BackBuillder\Bundle\Registry\Repository                    $registryRepository
     */
    public function __construct(
        UserProviderInterface $userProvider,
        $bbapp,
        $nonceDir,
        $lifetime = 300,
        $registryRepository = null,
        EncoderFactoryInterface $encoderFactory = null
    ) {
        $this->bbapp = $bbapp;
        parent::__construct($userProvider, $nonceDir, $lifetime, $registryRepository, $encoderFactory);
    }

    /**
     * Attempts to authenticates a TokenInterface object.
     *
     * @param  TokenInterface $token
     *
     * @return BBUserToken
     *
     * @throws SecurityException
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        $username = $token->getUsername();
        if (empty($username)) {
            $username = 'NONE_PROVIDED';
        }

        try {
            $user = $this->retrieveUser($username, $token);
            $this->checkAuthentication($user, $token);
            $this->checkNonce($token, $this->getEncodedPassword());
        } catch (\Exception $e) {
            $this->clearNonce($token);

            throw new Exception\BadCredentialsException('Invalid connection information.', 0, $e);
        }

        $bbUserId = $user->getBbUser()->getId();
        $this->bbapp->getEntityManager()->clear();
        $bbUser = $this->bbapp->getEntityManager()->find(User::class, $bbUserId);

        $authenticatedToken = new BBUserToken($user->getRoles());
        $authenticatedToken
            ->setUser($bbUser)
            ->setNonce($token->getNonce())
            ->setCreated(new \DateTime())
            ->setLifetime($this->lifetime)
        ;

        $this->writeNonceValue($authenticatedToken);

        return $authenticatedToken;
    }

    /**
     * Does additional checks on the user and token (like validating the
     * credentials).
     *
     * @param  UserInterface $user  The retrieved UserInterface instance
     * @param  BBUserToken   $token The BBUserToken token to be authenticated
     *
     * @throws Exception\AuthenticationException if the credentials could not be validated
     */
    protected function checkAuthentication(UserInterface $user, BBUserToken $token)
    {
        $password = $this->getPassword();

        if (empty($password)) {
            throw new Exception\BadCredentialsException('The presented password cannot be empty.');
        }

        if (!($user instanceof LdapUser) || null === $user->getBbUser()) {
            throw new Exception\UnsupportedUserException('Unsupported user.');
        }

        if (null === $this->userProvider->getLdap()) {
            throw new Exception\AuthenticationServiceException('No LDAP adapter defined.');
        }

        try {
            $this->userProvider->getLdap()->bind($user->getDn(), $password);
        } catch (\Exception $ex) {
            throw new Exception\BadCredentialsException('The presented password is invalid.', 0, $ex);
        }
    }

    /**
     * Retrieves the user from an implementation-specific location.
     *
     * @param  string        $username The username to retrieve
     * @param  BBUserToken   $token    The Token
     *
     * @return UserInterface The user
     *
     * @throws AuthenticationException if the credentials could not be validated
     */
    protected function retrieveUser($username, BBUserToken $token)
    {
        if ($token->getUser() instanceof User) {
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

    /**
     * Returns the plain text posted password.
     *
     * @return string|null
     */
    private function getPassword()
    {
        $request = $this->bbapp->getRequest();

        return $request->request->get('password');
    }

    /**
     * Returns the encoded posted password.
     *
     * @return string
     */
    private function getEncodedPassword()
    {
        return $this
            ->bbapp
            ->getSecurityContext()
            ->getEncoderFactory()
            ->getEncoder('BackBee\Security\User')
            ->encodePassword($this->getPassword(), '')
        ;
    }
}
