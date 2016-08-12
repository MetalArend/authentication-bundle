<?php

namespace Kuleuven\AuthenticationBundle\DependencyInjection;

use Kuleuven\AuthenticationBundle\Model\KuleuvenUser;
use Kuleuven\AuthenticationBundle\Security\KuleuvenUserToken;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationEntryPoint;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationListener;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationListenerFactory;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationProvider;
use Kuleuven\AuthenticationBundle\Service\LdapService;
use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Service\ShibbolethUserProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class KuleuvenAuthenticationExtension extends Extension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->addClassesToCompile([
            ShibbolethAuthenticationListenerFactory::class,
            ShibbolethAuthenticationEntryPoint::class,
            ShibbolethAuthenticationProvider::class,
            ShibbolethAuthenticationListener::class,
            ShibbolethServiceProvider::class,
            ShibbolethUserProvider::class,
            LdapService::class,
            KuleuvenUserToken::class,
            KuleuvenUser::class,
        ]);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Attribute definitions
        if (isset($config['authentication_attribute_definitions'])) {
            $container->setParameter('authentication_attribute_definitions', $config['authentication_attribute_definitions']);
        } elseif (!$container->hasParameter('authentication_attribute_definitions')) {
            $container->setParameter('authentication_attribute_definitions', []);
        }

        // Attribute requirements
        $container->setParameter('authentication_attribute_requirements', $config['authentication_attribute_requirements']);

        // Attribute overwrites
        $container->setParameter('authentication_attribute_overwrites_enabled', $config['authentication_attribute_overwrites_enabled']);
        if (isset($config['authentication_attribute_overwrites'])) {
            $container->setParameter('authentication_attribute_overwrites', $config['authentication_attribute_overwrites']);
        } elseif (!$container->hasParameter('authentication_attribute_overwrites')) {
            $container->setParameter('authentication_attribute_overwrites', []);
        }

        // Attribute LDAP overwrites
        $container->setParameter('authentication_attribute_ldap_enabled', $config['authentication_attribute_ldap_enabled']);
        $container->setParameter('authentication_attribute_ldap_filter', $config['authentication_attribute_ldap_filter']);

        // Attribute header overwrites
        $container->setParameter('authentication_attribute_headers_enabled', $config['authentication_attribute_headers_enabled']);

        // Shibboleth
        $container->setParameter('shibboleth_is_secured_handler', $config['shibboleth_is_secured_handler']);
        $container->setParameter('shibboleth_handler_path', $config['shibboleth_handler_path']);
        $container->setParameter('shibboleth_status_path', $config['shibboleth_status_path']);
        $container->setParameter('shibboleth_session_login_path', $config['shibboleth_session_login_path']);
        $container->setParameter('shibboleth_session_logout_path', $config['shibboleth_session_logout_path']);
        $container->setParameter('shibboleth_session_logout_target', $config['shibboleth_session_logout_target']);
        $container->setParameter('shibboleth_session_overview_path', $config['shibboleth_session_overview_path']);
        $container->setParameter('shibboleth_username_attribute', $config['shibboleth_username_attribute']);
        $container->setParameter('shibboleth_authenticated_attribute', $config['shibboleth_authenticated_attribute']);
        $container->setParameter('shibboleth_logout_url_attribute', $config['shibboleth_logout_url_attribute']);
        $container->setParameter('shibboleth_default_charset', $config['shibboleth_default_charset']);

        // LDAP
        $container->setParameter('ldap_rdn', $config['ldap_rdn']);
        if (!$container->hasParameter('ldap_rdn')) {
            throw new InvalidArgumentException('ldap_rdn parameter is required');
        }
        $container->setParameter('ldap_password', $config['ldap_password']);
        if (!$container->hasParameter('ldap_password')) {
            throw new InvalidArgumentException('ldap_password parameter is required');
        }
        $container->setParameter('ldap_base', $config['ldap_base']);
        $container->setParameter('ldap_domain', $config['ldap_domain']);
        $container->setParameter('ldap_port', $config['ldap_port']);
        $container->setParameter('ldap_encryption', $config['ldap_encryption']);
        $container->setParameter('ldap_referrals', $config['ldap_referrals']);
        $container->setParameter('ldap_version', $config['ldap_version']);
        $container->setParameter('ldap_debug', $config['ldap_debug']);

        // Person Data API
        $container->setParameter('person_data_api_url', $config['person_data_api_url']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

    }
}
