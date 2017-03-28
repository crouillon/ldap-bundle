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

namespace LpDigital\Bundle\LdapBundle\Test\Security\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use LpDigital\Bundle\LdapBundle\Security\Handler\ForbiddenFailureHandler;

/**
 * Test suite for ForbiddenFailureHandler
 *
 * @copyright ©2017 - Lp digital
 * @author    Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers    LpDigital\Bundle\LdapBundle\Security\Handler\ForbiddenFailureHandler
 */
class ForbiddenFailureHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers LpDigital\Bundle\LdapBundle\Security\Handler\ForbiddenFailureHandler::onAuthenticationFailure()
     * @expectedException     BackBee\Controller\Exception\FrontControllerException
     * @expectedExceptionCode 401
     */
    public function testOnFailure()
    {
        $handler = new ForbiddenFailureHandler();
        $handler->onAuthenticationFailure(new Request(), new UsernameNotFoundException('User not found'));
    }
}
