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

namespace LpDigital\Bundle\LdapBundle\Test\Mock;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

/**
 * Mock object of RememberMe service.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class MockRememberMeService implements RememberMeServicesInterface
{

    /**
     * {@inheritdoc}
     */
    public function autoLogin(Request $request)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function loginFail(Request $request)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function loginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        //
    }
}
