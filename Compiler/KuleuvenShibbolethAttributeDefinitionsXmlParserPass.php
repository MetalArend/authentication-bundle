<?php

namespace Kuleuven\AuthenticationBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KuleuvenShibbolethAttributeDefinitionsXmlParserPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $xmlPath;

    /**
     * @var array
     */
    protected $multivalues;

    /**
     * @param string $xmlPath
     */
    public function __construct($xmlPath)
    {
        $this->xmlPath = $xmlPath;

        // Hard-coded, until there is a way to read this
        $this->multivalues = [
            "eppn"                     => false,
            "affiliation"              => true,
            "unscoped-affiliation"     => true,
            "entitlement"              => false,
            "targeted-id"              => false,
            "persistent-id"            => false,
            "primary-affiliation"      => false,
            "nickname"                 => false,
            "primary-orgunit-dn"       => false,
            "orgunit-dn"               => true,
            "org-dn"                   => false,
            "cn"                       => false,
            "sn"                       => false,
            "givenName"                => false,
            "mail"                     => false,
            "uid"                      => false,
            "telephoneNumber"          => true,
            "title"                    => false,
            "description"              => false,
            "facsimileTelephoneNumber" => true,
            "postalAddress"            => true,
            "ou"                       => true,
            "roomNumber"               => true,
            "KULluditServer"           => false,
            "KULprimouNumber"          => true,
            "KULouNumber"              => true,
            "KULtap"                   => false,
            "KULemployeeType"          => true,
            "KULdipl"                  => true,
            "KULopl"                   => true,
            "KULstamnr"                => false,
            "KULid"                    => false,
            "KULlibisnr"               => false,
            "KULstudentType"           => true,
            "KULcampus"                => false,
            "userAppUserID"            => false,
            "syncoreLogonCode"         => false,
            "KULMoreUnifiedUID"        => false,
            "KULCardApplicationId"     => true,
            "KULCardSN"                => true,
            "KULPreferredMail"         => false,
            "KULMainLocation"          => true,
            "KULAssocUCCtag"           => true,
            "KULOfficialGivenName"     => false,
            "logoutURL"                => false,
            "uidToledo"                => false,
            "aid"                      => false,
        ];
    }

    public function process(ContainerBuilder $container)
    {
        // Add default Shibboleth definitions
        // https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAttributeAccess
        $attributeDefinitions = [
            'Shib-Application-ID'         => ['id' => 'Shib-Application-ID', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-Session-ID'             => ['id' => 'Shib-Session-ID', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-Identity-Provider'      => ['id' => 'Shib-Identity-Provider', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-Authentication-Instant' => ['id' => 'Shib-Authentication-Instant', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-Authentication-Method'  => ['id' => 'Shib-Authentication-Method', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-AuthnContext-Class'     => ['id' => 'Shib-AuthnContext-Class', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-AuthnContext-Decl'      => ['id' => 'Shib-AuthnContext-Decl', 'names' => [], 'aliases' => [], 'multivalue' => false],
            'Shib-Handler'                => ['id' => 'Shib-Handler', 'names' => [], 'aliases' => [], 'multivalue' => false],
        ];

        $xml = simplexml_load_file($this->xmlPath);

        /** @var \SimpleXMLElement $xmlElement */
        foreach ($xml->children() as $xmlElement) {
            $name = (string)$xmlElement['name'];
            $id = (string)$xmlElement['id'];
            $aliases = array_filter(explode(' ', (string)$xmlElement['aliases']));
            if (!isset($attributeDefinitions[$id])) {
                $attributeDefinitions[$id] = [
                    'id'         => $id,
                    'names'      => [$name],
                    'aliases'    => !empty($aliases) ? $aliases : [],
                    'multivalue' => isset($this->multivalues[$id]) ? $this->multivalues[$id] : null,
                ];
            } else {
                $attributeDefinitions[$id]['names'][] = $name;
                foreach ($aliases as $alias) {
                    if (!in_array($alias, $attributeDefinitions[$id]['aliases'])) {
                        $attributeDefinitions[$id]['aliases'][] = $alias;
                    }
                }
            }
            foreach ($aliases as $alias) {
                $attributeDefinitions[$alias] =& $attributeDefinitions[$id];
            }
        }

        $container->setParameter('kuleuven_shibboleth_attribute_definitions', $attributeDefinitions);
    }
}
