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

namespace LpDigital\Bundle\LdapBundle\Test\Security\Listener;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

use BackBee\Security\Token\BBUserToken;

use LpDigital\Bundle\LdapBundle\Entity\LdapUser;
use LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener;
use LpDigital\Bundle\LdapBundle\Test\LdapTestCase;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockInvalidFailureHandler;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockInvalidSuccessHandler;
use LpDigital\Bundle\LdapBundle\Test\Mock\MockRememberMeService;

/**
 * Test suite for LdapAuthenticationListener
 *
 * @copyright ©2017 - Lp digital
 * @author    Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers    LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener
 */
class LdapAuthenticationListenerTest extends LdapTestCase
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LdapAutenticationListener
     */
    private $listener;

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        parent::setUp();

        $em = $this->bundle->getEntityManager();
        $metadata = [
            $em->getClassMetadata(LdapUser::class),
        ];

        $schema = new SchemaTool($em);
        $schema->createSchema($metadata);

        $container = $this->bundle->getApplication()->getContainer();

        $this->listener = new LdapAuthenticationListener(
            $this->bundle->getApplication()->getSecurityContext(),
            $this->bundle->getApplication()->getSecurityContext()->getAuthenticationManager(),
            'bundle.mockldap',
            new DefaultAuthenticationSuccessHandler(new HttpUtils()),
            new DefaultAuthenticationFailureHandler($container->get('controller'), new HttpUtils()),
            'login',
            'password',
            $container->get('ed'),
            $container->get('logging')
        );

        $this->listener->setRememberMeServices(new MockRememberMeService());
        $this->container = $container;
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::handle()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage This authentication method requires a session.
     */
    public function testInvalidRequest()
    {
        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->listener->handle($event);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::handle()
     */
    public function testAlreadyAuthenticated()
    {
        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));

        $this->bundle
            ->getApplication()
            ->getSecurityContext()
            ->setToken(new UsernamePasswordToken('good', 'good', 'bundle.mockldap'));

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->listener->handle($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::handle()
     */
    public function testBBAuthenticated()
    {
        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));

        $this->bundle
            ->getApplication()
            ->getSecurityContext()
            ->setToken(new BBUserToken());

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->listener->handle($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::handle()
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::onSuccess()
     */
    public function testValidCredentials()
    {
        $this->container->get('bundle.mockldap')->setOption('persist_on_missing', true);

        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));
        $request->request->set('login', 'found');
        $request->request->set('password', 'good');

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->listener->handle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNull($request->getSession()->get(Security::LAST_USERNAME));
        $this->assertNull($request->getSession()->get(Security::AUTHENTICATION_ERROR));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::handle()
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::onFailure()
     */
    public function testInvalidCredentials()
    {
        $this->container->get('bundle.mockldap')->setOption('persist_on_missing', true);

        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));
        $request->request->set('login', 'found');
        $request->request->set('password', 'notgood');

        $this->bundle
            ->getApplication()
            ->getSecurityContext()
            ->setToken(new UsernamePasswordToken('found', 'notgood', 'bundle.mockldap'));

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->listener->handle($event);
        $response = $event->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('found', $request->getSession()->get(Security::LAST_USERNAME));
        $this->assertInstanceof(
            BadCredentialsException::class,
            $request->getSession()->get(Security::AUTHENTICATION_ERROR)->getPrevious()
        );
        $this->assertNull($this->bundle->getApplication()->getSecurityContext()->getToken());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::onSuccess()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Authentication Success Handler did not return a Response.
     */
    public function testInvalidSuccessHandler()
    {
        $listener = new LdapAuthenticationListener(
            $this->bundle->getApplication()->getSecurityContext(),
            $this->bundle->getApplication()->getSecurityContext()->getAuthenticationManager(),
            'bundle.mockldap',
            new MockInvalidSuccessHandler(),
            new MockInvalidFailureHandler(),
            'login',
            'password',
            $this->container->get('ed'),
            $this->container->get('logging')
        );
        $this->container->get('bundle.mockldap')->setOption('persist_on_missing', true);

        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));
        $request->request->set('login', 'found');
        $request->request->set('password', 'good');

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener->handle($event);
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Listener\LdapAuthenticationListener::onFailure()
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Authentication Failure Handler did not return a Response.
     */
    public function testInvalidFailureHandler()
    {
        $listener = new LdapAuthenticationListener(
            $this->bundle->getApplication()->getSecurityContext(),
            $this->bundle->getApplication()->getSecurityContext()->getAuthenticationManager(),
            'bundle.mockldap',
            new MockInvalidSuccessHandler(),
            new MockInvalidFailureHandler(),
            'login',
            'password',
            $this->container->get('ed'),
            $this->container->get('logging')
        );
        $this->container->get('bundle.mockldap')->setOption('persist_on_missing', true);

        $request = new Request();
        $request->setSession(new Session($this->container->get('session.storage')));
        $request->request->set('login', 'found');
        $request->request->set('password', 'notgood');

        $event = new GetResponseEvent(
            $this->bundle->getApplication()->getController(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener->handle($event);
    }
}
