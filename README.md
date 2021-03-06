This bundle adds a shibboleth authentication firewall to your Symfony3 project.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/acd48adb-ec80-4c6b-ad7d-07585b93ec83/mini.png)](https://insight.sensiolabs.com/projects/acd48adb-ec80-4c6b-ad7d-07585b93ec83)
[![Build Status](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/MetalArend/authentication-bundle/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/kuleuven/authentication-bundle/v/stable)](https://packagist.org/packages/kuleuven/authentication-bundle)
[![Dependency Status](https://www.versioneye.com/user/projects/57d8018b430747003e149bde/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/57d8018b430747003e149bde)
[![Latest Unstable Version](https://poser.pugx.org/kuleuven/authentication-bundle/v/unstable)](https://packagist.org/packages/kuleuven/authentication-bundle)
[![Dependency Status](https://www.versioneye.com/user/projects/57d80184430747004e18e260/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/57d80184430747004e18e260)

Installation
============

Download the Bundle
-------------------

Open a command console, enter your project directory and execute the following command
to download the latest stable version of this bundle:

```bash
$ composer require kuleuven/authentication-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Enable the Bundle
-----------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Kuleuven\AuthenticationBundle\KuleuvenAuthenticationBundle(),
        );

        // ...
    }

    // ...
}
```

Shibboleth
==========

Setup the Symfony firewall
--------------------------

```yml
# app/config/security.yml
security:
    ...
    providers:
        ...
        kuleuven_authentication.service.shibboleth_user_provider:
            id: kuleuven_authentication.service.shibboleth_user_provider
    ...
    firewalls:
        ...
        kuleuven_authentication:
            pattern: ^/secured # change this to your application's secured path
            stateless: true
            kuleuven_authentication: ~
            logout:
                path: /logout
                success_handler: kuleuven_authentication.security.http.logout.handler
```

By default, the bundle will use a default Shibboleth user provider.
This is an in-memory user provider that will get your user on the fly, based on the server attributes.

You can setup the firewall to check specific attribute requirements before authentication.
By default, this is set to the Shib-Identity-Provider needed to be 'urn:mace:kuleuven.be:kulassoc:kuleuven.be'.

```yml
# app/config/config.yml
...
kuleuven_authentication:
    authentication_attribute_requirements: { Shib-Identity-Provider: 'urn:mace:kuleuven.be:kulassoc:kuleuven.be' }
```

Setup Shibboleth in the .htaccess file in your public folder
------------------------------------------------------------

```apache
# web/.htaccess
# Shibboleth
<IfModule mod_shib>
    AuthType shibboleth
    Require shibboleth
    ShibRequireSession On
</IfModule>
```

Change Shibboleth Service Provider settings (optional)
------------------------------------------------------

```yml
# app/config/config.yml
...
kuleuven_authentication:
    shibboleth_is_secured_handler: true
    shibboleth_handler_path: /Shibboleth.sso
    shibboleth_status_path: /Status
    shibboleth_session_login_path: /Login
    shibboleth_session_logout_path: /Logout
    shibboleth_session_logout_target: ~
    shibboleth_session_overview_path: /Session
    shibboleth_username_attribute: Shib-Person-uid
    shibboleth_authenticated_attribute: Shib-Identity-Provider
    shibboleth_logout_url_attribute: Shib-logoutURL
    shibboleth_default_charset: ISO-8859-1
```

Overwrite Shibboleth server attributes (optional)
-------------------------------------------------

The Shibboleth firewall will by default use the server environment.
To be succesfully authenticated, at least two attributes should be present:

- the 'Shib-Identity-Provider' attribute will tell you which provider provided your identity
- the 'Shib-Person-uid' attribute contains your identity's uid

If you don't have Shibboleth running locally, you could add these attributes manually to your server environment,
or add them to the $_SERVER array in for example your app_dev.php.

####Parameter injector

This bundle however lets you overwrite any attribute from within your parameters.yml,
through the '\Kuleuven\AuthenticationBundle\Service\ParameterAttributesProvider' service that uses
the 'authentication_attribute_overwrites' parameter to inject an array of server attributes.

By default this feature is disabled, so you have to explicitly enable it.

```yml
# app/config/config_dev.yml
kuleuven_authentication:
    ...
    authentication_attribute_overwrites_enabled: true
```

Now you can add your overwrites to your parameters.yml.

```yml
# app/config/parameters.yml
parameters:
    ...
    authentication_attribute_overwrites:
        Shib-Identity-Provider: 'urn:mace:kuleuven.be:kulassoc:kuleuven.be'
        Shib-Person-uid: '<(string)your-uid>'
```

The parameter injector will always overwrite any other server attributes,
unless you would overwrite the priority of the service. By default the priority is set to -INF.

####Header injector

You could also overwrite specific attributes in your request,
through the '\Kuleuven\AuthenticationBundle\Service\HeaderAttributesProvider' service that uses
the symfony request headers to inject an array of server attributes.

By default this feature is disabled, so you have to explicitly enable it.

```yml
# app/config/config_dev.yml
kuleuven_authentication:
    ...
    authentication_attribute_headers_enabled: true
```

Now you can use something like Requestly to overwrite attributes in your application,
by sending them as headers with your request.

The header injector will always overwrite any other server attributes,
unless you would overwrite the priority of the service. By default the priority is set to -INF.

####LDAP attributes injector

You can use LDAP to request some of the server attributes from a specific person,
through the '\Kuleuven\AuthenticationBundle\Service\LdapAttributesProvider' service that uses
the 'authentication_attribute_ldap_filter' parameter to inject an LDAP result array of server attributes.

By default this feature is disabled, so you have to explicitly enable it.
 
```yml
# app/config/config_dev.yml
kuleuven_authentication:
    ...
    authentication_attribute_ldap_enabled: true
```

Once enabled, you can add your LDAP filter. Make sure the filter is unique enough to only provide one user.

```yml
# app/config/parameters.yml
parameters:
    ...
    authentication_attribute_ldap_filter: {uid: '<(string)your-uid>'}
```

It might be possible that you also need to enable LDAP - if so, jump to the LDAP chapter further down.

####Custom injector

If you want to add other services to populate your server attributes,
they should implement '\Kuleuven\AuthenticationBundle\Service\AttributesInjectionProviderInterface',
and should be tagged with 'kuleuven_authentication.shibboleth_attributes_injector'.

```yml
# app/config/services.yml
    ...
    my_attributes_provider:
        class: "%my_attributes_provider.class%"
        tags:
            - { name: kuleuven_authentication.shibboleth_attributes_injector }
```

Change the default firewall settings (optional)
-----------------------------------------------

For more control, there are two more firewall settings that can be overwritten:

- the 'provider' value defines the user provider - defaults to "kuleuven_authentication.service.shibboleth_user_provider"
- the 'default_roles' value defines some default roles - defaults to an empty array

```yml
# app/config/security.yml
security:
    ...
    firewalls:
        ...
        kuleuven_authentication:
            ...
            kuleuven_authentication:
                provider: 'kuleuven_authentication.service.shibboleth_user_provider'
                default_roles: ~
```

Overwrite the attribute definitions
-----------------------------------

By default, the bundle exposes several Shibboleth attributes through the user token [KuleuvenUserToken](Security/KuleuvenUserToken.php)
or the user [KuleuvenUser](Model/KuleuvenUser.php). Attributes can be accessed through `getAttribute`,
`getSingleAttribute`, `getArrayAttribute` or `hasAttributeValue`, with their id or aliases as the argument.

The [built-in SP variables](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAttributeAccess) are:

| Variable                        | Meaning                                                                                                               |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Shib-Application-ID             | The applicationId property derived for the request.                                                                   |
| Shib-Authentication-Instant     | The ISO timestamp provided by the IdP indicating the time of authentication.                                          |
| Shib-Authentication-Method      | The AuthenticationMethod or <AuthnContextClassRef> value supplied by the IdP, if any.                                 |
| Shib-AuthnContext-Class         | The AuthenticationMethod or <AuthnContextClassRef> value supplied by the IdP, if any.                                 |
| Shib-AuthnContext-Decl          | The <AuthnContextDeclRef> value supplied by the IdP, if any.                                                          |
| Shib-Handler                    | The self-referential base location of the SP's "handlers" for use by applications in requesting login, logout, etc.   |
| Shib-Identity-Provider          | The entityID of the IdP that authenticated the user associated with the request.                                      |
| Shib-Session-ID                 | The internal session key assigned to the session associated with the request.                                         |

The KU Leuven provides a [long list of usable attributes](https://shib.kuleuven.be/download/sp/2.x/attribute-map.xml).
A non-exhaustive list:

| id                          | aliases                                                 | multivalue |
| --------------------------- | ------------------------------------------------------- | ---------- |
| Shib-Application-ID         |                                                         | false      |
| Shib-Session-ID             |                                                         | false      |
| Shib-Identity-Provider      |                                                         | false      |
| Shib-Authentication-Instant |                                                         | false      |
| Shib-Authentication-Method  |                                                         | false      |
| Shib-AuthnContext-Class     |                                                         | false      |
| Shib-AuthnContext-Decl      |                                                         | false      |
| Shib-Handler                |                                                         | false      |
| eppn                        | user                                                    | false      |
| affiliation                 | Shib-EP-ScopedAffiliation, eduPersonScopedAffiliation   | true       |
| unscoped-affiliation        | Shib-EP-UnscopedAffiliation, eduPersonAffiliation       | true       |
| entitlement                 | Shib-EP-Entitlement, eduPersonEntitlement               | false      |
| targeted-id                 | Shib-TargetedID, eduPersonTargetedID                    | false      |
| persistent-id               |                                                         | false      |
| primary-affiliation         | Shib-EP-PrimaryAffiliation, eduPersonPrimaryAffiliation | false      |
| nickname                    | Shib-EP-Nickname, eduPersonNickName                     | false      |
| primary-orgunit-dn          | Shib-EP-PrimaryOrgUnitDN, eduPersonPrimaryOrgUnitDN     | false      |
| orgunit-dn                  | Shib-EP-OrgUnitDN, eduPersonOrgUnitDN                   | true       |
| org-dn                      | Shib-EP-OrgDN, eduPersonOrgDN                           | false      |
| cn                          | Shib-Person-commonName                                  | false      |
| sn                          | Shib-Person-surname                                     | false      |
| givenName                   | Shib-Person-givenName                                   | false      |
| mail                        | Shib-Person-mail                                        | false      |
| uid                         | Shib-Person-uid                                         | false      |
| telephoneNumber             | Shib-Person-telephoneNumber                             | true       |
| title                       |                                                         | false      |
| initials                    |                                                         | ?          |
| description                 |                                                         | false      |
| carLicense                  |                                                         | ?          |
| departmentNumber            |                                                         | ?          |
| displayName                 |                                                         | ?          |
| employeeNumber              |                                                         | ?          |
| employeeType                |                                                         | ?          |
| preferredLanguage           |                                                         | ?          |
| manager                     |                                                         | ?          |
| seeAlso                     |                                                         | ?          |
| facsimileTelephoneNumber    | Shib-Person-facsimileTelephoneNumber                    | true       |
| postalAddress               | Shib-Person-postalAddress                               | true       |
| street                      |                                                         | ?          |
| postOfficeBox               |                                                         | ?          |
| postalCode                  |                                                         | ?          |
| st                          |                                                         | ?          |
| l                           |                                                         | ?          |
| o                           |                                                         | ?          |
| ou                          | Shib-Person-ou                                          | true       |
| businessCategory            |                                                         | ?          |
| physicalDeliveryOfficeName  |                                                         | ?          |
| roomNumber                  | Shib-Person-roomNumber                                  | true       |
| KULluditServer              | Shib-KUL-luditServer                                    | false      |
| KULprimouNumber             | Shib-KUL-PrimouNumber                                   | true       |
| KULouNumber                 | Shib-KUL-ouNumber                                       | true       |
| KULtap                      | Shib-KUL-tap                                            | false      |
| KULemployeeType             | Shib-KUL-employeeType                                   | true       |
| KULdipl                     | Shib-KUL-dipl                                           | true       |
| KULopl                      | Shib-KUL-opl                                            | true       |
| KULstamnr                   | Shib-KUL-stamnr                                         | false      |
| KULid                       | Shib-KUL-id                                             | false      |
| KULlibisnr                  | Shib-KUL-libisnr                                        | false      |
| KULstudentType              | Shib-KUL-studentType                                    | true       |
| KULcampus                   | Shib-KUL-campus                                         | false      |
| userAppUserID               |                                                         | false      |
| syncoreLogonCode            |                                                         | false      |
| KULMoreUnifiedUID           |                                                         | false      |
| KULCardApplicationId        |                                                         | true       |
| KULCardSN                   |                                                         | true       |
| KULPreferredMail            |                                                         | false      |
| KULMainLocation             |                                                         | true       |
| KULAssocUCCtag              |                                                         | true       |
| KULOfficialGivenName        |                                                         | false      |
| logoutURL                   | Shib-logoutURL                                          | false      |
| uidToledo                   | Shib-uidToledo                                          | false      |
| aid                         | Shib-assoc-aid                                          | false      |
| HomeOrganization            |                                                         | ?          |
| HomeOrganizationType        |                                                         | ?          |
| KULAssocSAPID               |                                                         | ?          |
| KULAssocLibisPID            |                                                         | ?          |
| KULAssocLibisNbr            |                                                         | ?          |
| KULAssocMigrateID           |                                                         | ?          |

You can always extend these definitions by adding definitions into the parameter 'authentication_attribute_definitions'.

LDAP
====

Change LDAP settings (optional)
------------------------------------------------------

If you have your own LDAP credentials, you may use those by setting the LDAP parameters.
The default settings however should work as long as you are connected to the KU Leuven network.

```yml
# app/config/parameters.yml
parameters:
    ldap_rdn: ''
    ldap_password: ''
    ldap_base: 'ou=people,dc=kuleuven,dc=be'
    ldap_domain: 'ldap.kuleuven.be'
    ldap_port: '389'
```

Impersonate users (optional)
----------------------------

Through LDAP, we have the possibility to impersonate any KU Leuven member.
 
Allthough the Shibboleth authentication is stateless itself, for this to work it needs to save a token to the session.
You don't need to change the stateless key though, as the Shibboleth authentication will still check
the Shibboleth session of the source user to make sure the session stays alive.

To enable this, you need to add an LDAP user provider. However, we also still need our Shibboleth user provider.
So let's add a chain_provider, and overwrite the firewall provider.
Also add the switch_user attribute, and detect some default_role to check if a user may impersonate. 

```yml
# app/config/security.yml
security:
    ...
    providers:
       chain_provider:
            chain:
                providers: [kuleuven_authentication.service.shibboleth_user_provider, kuleuven_authentication.service.shibboleth_via_ldap_user_provider]
        kuleuven_authentication.service.shibboleth_user_provider:
            id: kuleuven_authentication.service.shibboleth_user_provider 
        kuleuven_authentication.service.shibboleth_via_ldap_user_provider:
            id: kuleuven_authentication.service.shibboleth_via_ldap_user_provider
    ...
    firewalls:
        ...
        kuleuven_authentication:
            ...
            kuleuven_authentication:
                provider: chain_provider
                default_roles: [ROLE_SHIBBOLETH_AUTHENTICATED]
            switch_user: { role: ROLE_SHIBBOLETH_AUTHENTICATED, parameter: _switch_user }
```

Typical development setup
=========================

Both using the overwrites and LDAP, there is a very easy setup to enable local development without installing Shibboleth.

Enable the overwrites and provide the overwrite for the Shib-Identity-Provider attribute in config_dev.yml.
Also enable LDAP in config_dev.yml.

```yml
# app/config/config_dev.yml
...
kuleuven_authentication:
    authentication_attribute_overwrites_enabled: true
    authentication_attribute_overwrites: {Shib-Identity-Provider: 'urn:mace:kuleuven.be:kulassoc:kuleuven.be'}
    authentication_attribute_ldap_enabled: true
```

Add your uid by adding this to your parameters.yml(.dist). It will be ignored in production.

```yml
# app/config/parameters.yml.dist
...
parameters:
    authentication_attribute_ldap_filter: {uid: '<(string)your-uid>'}
```

Extra
=====

There is a default route "/authentication".

Check if you are behind a certain firewall with the FirewallHelper service.

Upcoming
========

Security
--------
- Make it possible to add your own attribute-map.xml file (including external url) - downloading in compiler pass?
- Find a way to detect which fields are multivalue, instead of hard-coding it into the AttributeDefinitionsProvider
- Add providerKey in token support checks

Logging
-------
- Use one central proxy logger for the authentication bundle
- Implement LoggerAware in some extra classes

Data Collector
--------------
- Add authentication (including use_headers), LDAP, PersonDataAPI and impersonation to DataCollector

Switch User
-----------
- Instead of overwriting the switchuser_listener, add a new Security Factory
- Add KuleuvenSwitchUserToken
- ShibbolethSwitchUserPersistenceSubscriber should authenticate token in onKernelRequest with ShibbolethAuthenticationProvider

Docker
------
- Create Docker container with https://shib.kuleuven.be/docs/sp/2.x/install-sp-2.x-windows2008.html
    - For KU Leuven: To request a commercial certificate, please refer to: https://certificates.kuleuven.be
    - SSL certificates: Download the certificate from http://shib.kuleuven.be/download/metadata/metadata.associatie.kuleuven.be.crt
    - Metadata provider: https://shib.kuleuven.be/download/metadata/metadata-kuleuven.xml
    - Service providers part of the KU Leuven federation will have to configure the MetadataProvider to get the metadata from https://shib.kuleuven.be/download/metadata/metadata-kuleuven.xml
    - For SP's part of the Association KU Leuven federation the URL is https://shib.kuleuven.be/download/metadata/metadata-kulassoc.xml
    - For Service Providers part of the K.U.Leuven federation or the Association K.U.Leuven federation, we have configured such an attribute-map: https://shib.kuleuven.be/download/sp/2.x/attribute-map.xml

Extra
-----
- Send notice if LDAP filter returns more than 1 user
- Provide examples: how to add an automatic user save on visit

References
==========

[![Reference Status](https://www.versioneye.com/php/kuleuven:authentication-bundle/reference_badge.svg?style=flat-square)](https://www.versioneye.com/php/kuleuven:authentication-bundle/references)
