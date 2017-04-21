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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\HttpUtils;

use BackBee\Security\Context\AbstractContext;
use BackBee\Security\Context\ContextInterface;
use BackBee\Security\SecurityContext;

use LpDigital\Bundle\LdapBundle\Authentication\Provider\LdapAuthenticationProvider;
use LpDigital\Bundle\LdapBundle\Ldap;
use LpDigital\Bundle\LdapBundle\Security\LdapUserProvider;
use LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener;

/**
 * A LDAP firewall context.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class LdapContext extends AbstractContext implements ContextInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Context constructor.
     *
     * @param SecurityContext $context
     */
    public function __construct(SecurityContext $context)
    {
        parent::__construct($context);

        $this->container = $context->getApplication()->getContainer();
    }

    /**
     * Returns an array of authentication listeners for this context.
     *
     * @param  array $config       The firewall configuration.
     *
     * @return ListenerInterface[] An array  of authentication listeners.
     */
    public function loadListeners($config)
    {
        $ldapConfig = isset($config['form_login_ldap'])
                ? $config['form_login_ldap']
                : (isset($config['http_basic_ldap']) ? $config['http_basic_ldap'] : []);

        if (null === $serviceId = $this->getLdapServiceId($ldapConfig)) {
            return [];
        }

        $this->addAuthProvider($config, $serviceId);

        $listener = new LdapAuthenticationListener(
            $this->_context,
            $this->_context->getAuthenticationManager(),
            $serviceId,
            $this->createSuccessHandler($ldapConfig),
            $this->createFailureHandler($ldapConfig),
            isset($ldapConfig['username_parameter']) ? $ldapConfig['username_parameter'] : null,
            isset($ldapConfig['password_parameter']) ? $ldapConfig['password_parameter'] : null,
            $this->container->get('event.dispatcher'),
            $this->container->get('logging')
        );

        return [$listener];
    }

    /**
     * Returns the Ldap service id.
     *
     * @param  array $ldapConfig
     *
     * @return string|null
     */
    private function getLdapServiceId(array $ldapConfig)
    {
        if (empty($ldapConfig)) {
            return null;
        }

        $serviceId = isset($ldapConfig['service']) ? $ldapConfig['service'] : 'bundle.ldap';
        if (!$this->container->has($serviceId)) {
            return null;
        }

        return $serviceId;
    }

    /**
     * Adds a new authentication provider.
     *
     * @param array  $config
     * @param string $serviceId
     */
    private function addAuthProvider(array $config, $serviceId)
    {
        $service = $this->container->get($serviceId);
        $userProvider = $this->getDefaultProvider($config);

        $securityConfig = $this->container->get('config')->getSecurityConfig();
        if (isset($config['provider'])
            && isset($securityConfig['providers'])
            && isset($securityConfig['providers'][$config['provider']])
        ) {
            $options = $securityConfig['providers'][$config['provider']];
            foreach ($options as $option => $value) {
                $this->container->get($serviceId)->setOption($option, $value);
            }
        }

        if ($service instanceof Ldap) {
            $ldapConfig = $config['form_login_ldap'];
            if (isset($ldapConfig['dn_string'])) {
                $service->setOption('dn_string', $ldapConfig['dn_string']);
            }

            if ($userProvider instanceof LdapUserProvider) {
                $userProvider->setLdap($service);
                $authProvider = new LdapAuthenticationProvider($userProvider, new UserChecker(), $serviceId);
                $this->_context->getAuthenticationManager()->addProvider($authProvider);
            }
        }
    }

    /**
     * Creates an authentication success handler.
     *
     * @param  array $ldapConfig
     *
     * @return AuthenticationSuccessHandlerInterface
     */
    private function createSuccessHandler(array $ldapConfig)
    {
        if (isset($ldapConfig['success_handler'])
            && $this->container->has($ldapConfig['success_handler'])
            && ($this->container->get($ldapConfig['success_handler']) instanceof AuthenticationSuccessHandlerInterface)
        ) {
            return $this->container->get($ldapConfig['success_handler']);
        }

        return new DefaultAuthenticationSuccessHandler(new HttpUtils(), $ldapConfig);
    }

    /**
     * Creates an authentication failure handler.
     *
     * @param  array $ldapConfig
     *
     * @return AuthenticationFailureHandlerInterface
     */
    private function createFailureHandler(array $ldapConfig)
    {
        if (isset($ldapConfig['failure_handler'])
            && $this->container->has($ldapConfig['failure_handler'])
            && ($this->container->get($ldapConfig['failure_handler']) instanceof AuthenticationFailureHandlerInterface)
        ) {
            return $this->container->get($ldapConfig['failure_handler']);
        }

        return new DefaultAuthenticationFailureHandler(
            $this->container->get('controller'),
            new HttpUtils(),
            $ldapConfig,
            $this->container->get('logging')
        );
    }
}
