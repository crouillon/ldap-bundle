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

namespace LpDigital\Bundle\LdapBundle\User;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A user entry from LDAP server.
 *
 * @copyright    ©2017 - Lp digital
 * @author       Charles Rouillon <charles.rouillon@lp-digital.fr>
 *
 * @ORM\Entity(repositoryClass="LpDigital\Bundle\LdapBundle\User\LdapUserProvider")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="bbx_ldap_user")
 */
class LdapUser implements UserInterface
{

    /**
     * The username.
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="username", type="string", nullable=false)
     */
    protected $username;

    /**
     * The data entry of the LDAP user.
     *
     * @var Entry
     *
     * @ORM\Column(name="entry", type="object", nullable=true)
     */
    protected $entry;

    /**
     * The creation date of the user.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    protected $created;

    /**
     * The last modification date of the user.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=false)
     */
    protected $modified;

    /**
     * The user's password.
     *
     * @var string
     */
    protected $password;

    /**
     * The user's roles.
     *
     * @var (Role|string)[]
     */
    protected $roles;

    /**
     * User constructor.
     *
     * @param string          $username The username.
     * @param string|null     $password Optional, the user's password.
     * @param (Role|string)[] $roles    Optional, the user's roles (default: empty).
     */
    public function __construct($username, $password = null, array $roles = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;

        $this->created = new \DateTime();
        $this->modified = $this->created;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * @return string|null The password if exists.
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Removes sensitive data from the user.
     *
     * @return LdapUser
     */
    public function eraseCredentials()
    {
        $this->password = null;

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return (Role|string)[] The user's roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Returns the salt.
     *
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the user's LDAP entry if exists.
     *
     * @return Entry|null
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * Sets the LDAP data entry for user.
     *
     * @param  Entry|null $entry
     *
     * @return LdapUser
     */
    public function setEntry(Entry $entry = null)
    {
        $this->entry = $entry;

        return $this;
    }

    /**
     * Returns the creation date of the user.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Returns the last modification date of the user.
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->created;
    }

    /**
     * Updates the last modification date on entity update.
     *
     * @ORM\PreUpdate
     * @codeCoverageIgnore
     */
    public function onPreUpdate()
    {
        $this->modified = new \DateTime();
    }
}
