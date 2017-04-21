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

namespace LpDigital\Bundle\LdapBundle\Security\Context;

use BackBee\Security\Context\RestfulContext;
use BackBee\Utils\Collection\Collection;

use LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapBBAuthenticationProvider;
use LpDigital\Bundle\LdapBundle\Ldap;

/**
 * Restful Security Context throw LDAP.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class RestfulLdapContext extends RestfulContext
{

    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        if (null !== $serviceId = Collection::get($config, 'restful:ldap:service')) {
            $ldapConfig = array_merge(
                [
                    'nonce_dir' => 'security/nonces',
                    'lifetime' => 1200,
                    'use_registry' => false,
                ],
                $config['restful']
            );

            $this->addAuthProvider($ldapConfig, $serviceId);
        }

        return [];
    }

    /**
     * Adds a new authentication provider.
     *
     * @param array  $config
     * @param string $serviceId
     */
    private function addAuthProvider(array $config, $serviceId)
    {
        $container = $this->_context->getApplication()->getContainer();
        $service = $container->get($serviceId);
        $userProvider = $this->getDefaultProvider($config);

        $securityConfig = $container->get('config')->getSecurityConfig();
        if (isset($config['provider'])
            && isset($securityConfig['providers'])
            && isset($securityConfig['providers'][$config['provider']])
        ) {
            $options = $securityConfig['providers'][$config['provider']];
            foreach ($options as $option => $value) {
                $container->get($serviceId)->setOption($option, $value);
            }
        }

        if ($service instanceof Ldap) {
            $ldapConfig = $config['ldap'];
            if (isset($ldapConfig['dn_string'])) {
                $service->setOption('dn_string', $ldapConfig['dn_string']);
            }

            $authProvider = new LdapBBAuthenticationProvider(
                $userProvider,
                $this->_context->getApplication(),
                $this->getNonceDirectory($config),
                $config['lifetime'],
                true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                $this->_context->getEncoderFactory()
            );
            $this->_context->getAuthenticationManager()->addProvider($authProvider);
        }
    }

    /**
     * Returns the nonce directory path.
     *
     * @param array $config
     *
     * @return string the nonce directory path
     */
    private function getNonceDirectory(array $config)
    {
        return $this->_context->getApplication()->getCacheDir().DIRECTORY_SEPARATOR.$config['nonce_dir'];
    }

    /**
     * Returns the repository to Registry entities.
     *
     * @return \BackBuillder\Bundle\Registry\Repository
     *
     * @codeCoverageIgnore
     */
    private function getRegistryRepository()
    {
        $repository = null;
        if (null !== $em = $this->_context->getApplication()->getEntityManager()) {
            $repository = $em->getRepository('BackBee\Bundle\Registry');
        }

        return $repository;
    }
}
