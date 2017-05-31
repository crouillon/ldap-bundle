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

namespace LpDigital\Bundle\LdapBundle\Test;

use Doctrine\ORM\Tools\SchemaTool;
use BackBee\Security\Group;

/**
 * Test suite for Ldap bundle class.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers       LpDigital\Bundle\LdapBundle\Ldap
 */
class LdapTest extends LdapTestCase
{

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::getLdapClients()
     */
    public function testGetLdapClients()
    {
        $defaultOptions = $this->invokeProperty($this->bundle, 'defaultOptions');
        $ldapConfig = [
            'ad1' => [],
            'ad2' => ['base_dn' => 'ad2 basedn'],
        ];

        $this->bundle->getConfig()->setSection('ldap', $ldapConfig, true);
        $ldapClients = $this->invokeMethod($this->bundle, 'getLdapClients');

        $this->assertTrue(is_array($ldapClients));
        $this->assertEquals(2, count($ldapClients));
        $this->assertEquals($defaultOptions['base_dn'], $this->bundle->getOption('ad1', 'base_dn'));
        $this->assertEquals($ldapConfig['ad2']['base_dn'], $this->bundle->getOption('ad2', 'base_dn'));
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::persistOnMissing()
     */
    public function testPersistOnMissing()
    {
        $this->assertFalse($this->bundle->persistOnMissing());

        $this->bundle->getConfig()->setSection('parameters', ['persist_on_missing' => true]);
        $this->assertTrue($this->bundle->persistOnMissing());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::getStoredAttributes()
     */
    public function testGetStoredAttributes()
    {
        $this->assertEquals([], $this->bundle->getStoredAttributes());

        $this->bundle->getConfig()->setSection('parameters', ['store_attributes' => ['cn', 'mail']]);
        $this->assertEquals(['cn', 'mail'], $this->bundle->getStoredAttributes());
    }

    /**
     * @covers LpDigital\Bundle\LdapBundle\Ldap::getDefaultBackBeeGroups()
     */
    public function testGetDefaultBackBeeGroups()
    {
        $em = $this->bundle->getEntityManager();
        $schema = new SchemaTool($em);
        $schema->createSchema([$em->getClassMetadata(Group::class)]);

        $group1 = new Group();
        $group1->setName('group1');
        $group2 = new Group();
        $group2->setName('group2');
        $em->persist($group1);
        $em->persist($group2);
        $em->flush();

        $this->bundle->getConfig()->setSection('parameters', ['default_backbee_groups' => [1, 'group2', 'unknown']]);
        $expected = [
            1 => $group1,
            2 => $group2
        ];

        $this->assertEquals($expected, $this->bundle->getDefaultBackBeeGroups());
    }
}
