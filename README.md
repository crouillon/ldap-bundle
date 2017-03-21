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


Configuration
-------------
Create and edit the configuration file `repository/Config/bundle/ldap/config.yml` in your BackBee project.

```yaml

```


---

*This project is supported by [Lp digital](http://www.lp-digital.fr/en/)*

**Lead Developer** : [@crouillon](https://github.com/crouillon)

Released under the GPL3 License
