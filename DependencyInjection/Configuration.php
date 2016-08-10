<?php

namespace Kuleuven\AuthenticationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kuleuven_authentication');

        /** @noinspection PhpUndefinedMethodInspection */
        $rootNode
            ->fixXmlConfig('attribute_definition')
            ->fixXmlConfig('overwrite')
            ->children()

                // Authentication
                ->arrayNode('authentication_attribute_definitions')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->booleanNode('multivalue')->defaultFalse()->end()
                            ->scalarNode('charset')->defaultValue('UTF-8')->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('authentication_attribute_overwrites_enabled')->defaultFalse()->end()
                ->arrayNode('authentication_attribute_overwrites')
                    ->useAttributeAsKey('id')
                    ->normalizeKeys(false)
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->booleanNode('authentication_attribute_ldap_enabled')->defaultFalse()->end()
                ->arrayNode('authentication_attribute_ldap_filter')
                    ->useAttributeAsKey('id')
                    ->normalizeKeys(false)
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('authentication_attribute_requirements')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->prototype('scalar')->end()
                    ->defaultValue(['Shib-Identity-Provider' => 'urn:mace:kuleuven.be:kulassoc:kuleuven.be'])
                ->end()

                // Shibboleth
                ->booleanNode('shibboleth_is_secured_handler')->defaultTrue()->end()
                ->scalarNode('shibboleth_handler_path')->defaultValue('/Shibboleth.sso')->end()
                ->scalarNode('shibboleth_status_path')->defaultValue('/Status')->end()
                ->scalarNode('shibboleth_session_login_path')->defaultValue('/Login')->end()
                ->scalarNode('shibboleth_session_logout_path')->defaultValue('/Logout')->end()
                ->scalarNode('shibboleth_session_logout_target')->defaultValue(null)->end()
                ->scalarNode('shibboleth_session_overview_path')->defaultValue('/Session')->end()
                ->scalarNode('shibboleth_username_attribute')->defaultValue('Shib-Person-uid')->end()
                ->scalarNode('shibboleth_authenticated_attribute')->defaultValue('Shib-Identity-Provider')->end()
                ->scalarNode('shibboleth_logout_url_attribute')->defaultValue('Shib-logoutURL')->end()
                ->scalarNode('shibboleth_default_charset')->defaultValue('ISO-8859-1')->end()

                // LDAP
                ->scalarNode('ldap_rdn')->defaultValue('')->end()
                ->scalarNode('ldap_password')->defaultValue('')->end()
                ->scalarNode('ldap_base')->defaultValue('ou=people,dc=kuleuven,dc=be')->end()
                ->scalarNode('ldap_domain')->defaultValue('ldap.kuleuven.be')->cannotBeEmpty()->end()
                ->scalarNode('ldap_port')->defaultValue('389')->end()
                ->enumNode('ldap_encryption')->values(['none', 'ssl', 'tls'])->defaultValue('none')->end()
                ->booleanNode('ldap_referrals')->defaultFalse()->end()
                ->booleanNode('ldap_version')->defaultValue('3')->end()
                ->booleanNode('ldap_debug')->defaultFalse()->end()

                // Person Data API
                ->scalarNode('person_data_api_url')->defaultValue('https://webwsp.aps.kuleuven.be/esap/public/odata/sap/zh_person_srv/Persons(\'%s\')?$format=json&$expand=WorkAddresses')->end()

            ->end();

        return $treeBuilder;
    }
}
