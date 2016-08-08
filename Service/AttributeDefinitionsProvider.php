<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class AttributeDefinitionsProvider implements AttributeDefinitionsProviderInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    protected $attributeDefinitions;

    protected $multivalues;

    /**
     * @param KernelInterface $kernel
     * @param array           $attributeDefinitions
     */
    public function __construct(KernelInterface $kernel, $attributeDefinitions)
    {
        $this->kernel = $kernel;

        $this->attributeDefinitions = $attributeDefinitions;

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

    public function getAttributeDefinitions()
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

        $resource = '@KuleuvenAuthenticationBundle/Resources/config/attribute-map/attribute-map.xml';
        try {
            $path = $this->kernel->locateResource($resource);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(sprintf('Unable to load "%s"', $resource), 0, $exception);
        }

        $xml = simplexml_load_file($path);

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

        foreach ($this->attributeDefinitions as $attributeDefinition) {
            $id = $attributeDefinition['id'];
            $aliases = isset($attributeDefinition['aliases']) ? $attributeDefinition['aliases'] : [];
            if (isset($attributeDefinitions[$id])) {
                // Remove already present aliases
                foreach ($attributeDefinitions['aliases'] as $alias) {
                    unset($attributeDefinitions[$alias]);
                }
            }
            $attributeDefinitions[$id] = $attributeDefinition;
            foreach ($aliases as $alias) {
                $attributeDefinitions[$alias] =& $attributeDefinitions[$id];
            }
        }

        return $attributeDefinitions;
    }
}
