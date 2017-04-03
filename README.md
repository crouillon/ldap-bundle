ldap-bundle
===========

[![Build Status](https://travis-ci.org/Lp-digital/ldap-bundle.svg?branch=master)](https://travis-ci.org/Lp-digital/ldap-bundle)
[![Code Climate](https://codeclimate.com/github/Lp-digital/ldap-bundle/badges/gpa.svg)](https://codeclimate.com/github/Lp-digital/ldap-bundle)
[![Test Coverage](https://codeclimate.com/github/Lp-digital/ldap-bundle/badges/coverage.svg)](https://codeclimate.com/github/Lp-digital/ldap-bundle/coverage)

**ldap-bundle** enables to easily implement LDAP authentication on BackBee instances.

Installation
------------
Edit the file `composer.json` of your BackBee project.

Add the new dependency to the bundle in the `require` section:
```json
# composer.json
...
    "require": {
        ...
        "lp-digital/ldap-bundle": "~1.0.0"
    },
...
```

Save and close the file.

Run a composer update on your project.


Activation
----------
Edit the file `repository/Config/bundles.yml` of your BackBee project.

Add the following line at the end of the file:
```yaml
# bundles configuration - repository/Config/bundles.yml
...
hauth: LpDigital\Bundle\LdapBundle\Ldap
```

Save and close the file.

Then launch the command to update database:
```
./backbee bundle:update ldap --force
```

Depending on your configuration, cache may need to be clear.


Enable LDAP on a front-side firewall for one website
----------------------------------------------------

To enable an LDAP authentication on a firewall, edit the file `repository/Config/security.yml and declare a new UserProvider allowing LDAP querying.

```yaml
# security.yml
firewalls:
  front_area:
    pattern: ^/
    provider: ldap
    form_login_ldap:
      service: bundle.ldap
    ...
providers:
  ldap:
    entity:
      class: LpDigital\Bundle\LdapBundle\Entity\LdapUser
    default_roles: [ROLE_USER]
    base_dn: 'CN=Users,DC=www,DC=ad,DC=sample,DC=com'          # optional, can be defined globally in bundle configuration
    search_dn: 'CN=ReadOnly,DC=www,DC=ad,DC=sample,DC=com'     # optional, can be defined globally in bundle configuration
    search_password: ***********                               # optional, can be defined globally in bundle configuration
    filter: '(sAMAccountName={username})'                      # optional, can be defined globally in bundle configuration
```

To define global configuration, create and edit file `repository/Config/bundle/ldap/config.yml`

```yaml
# config.yml
ldap:
    base_dn: 'CN=Users,DC=www,DC=ad,DC=sample,DC=com'
    search_dn: 'CN=ReadOnly,DC=www,DC=ad,DC=sample,DC=com'
    search_password: ***********
    filter: '(sAMAccountName={username})'
    persist_on_missing: true                                            # if true accept "unknown" new user (default: false)
    store_attributes: ['cn', 'description', 'name', 'mail', 'memberOf'] # the LDAP attributes to store

# overriding by site is also available
override_site:
    site1_uid:
        ldap:
            base_dn: 'CN=Users,DC=www,DC=ad,DC=sample,DC=com'
            search_dn: 'CN=ReadOnly,DC=www,DC=ad,DC=sample,DC=com'
            search_password: ***********
            filter: '(sAMAccountName={username})'
```

Depending on your configuration, cache may need to be clear.

---

*This project is supported by [Lp digital](http://www.lp-digital.fr/en/)*

**Lead Developer** : [@crouillon](https://github.com/crouillon)

Released under the GPL3 License
