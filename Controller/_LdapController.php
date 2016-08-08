<?php

namespace Kuleuven\AuthenticationBundle\Controller;

use Kuleuven\AuthenticationBundle\Service\LdapService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LdapController
{
    /**
     * @var LdapService
     */
    protected $ldapService;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param LdapService $ldapService
     * @param bool        $debug
     */
    public function __construct(LdapService $ldapService, $debug)
    {
        $this->ldapService = $ldapService;
        $this->debug = $debug;
    }


    public function searchAction(Request $request)
    {
        $username = $request->query->get('username', null);
        $firstName = $request->query->get('firstName', null);
        $lastName = $request->query->get('lastName', null);
        $email = $request->query->get('email', null);
        $limit = $request->query->get('limit', 20);
        $filter = array_filter([
            'uid'       => $username,
            'sn'        => $lastName,
            'givenname' => $firstName,
            'mail'      => $email,
        ]);
        $results = $this->ldapService->search($filter, ['uid', 'sn', 'givenname', 'mail', 'ou'], $limit, true);
        $data = [];
        foreach ($results as $index => $result) {
            if ('count' === $index) {
                $data[$index] = $result;
                continue;
            }
            $data[$index] = [
                'username'  => $result['uid'][0],
                'firstName' => $result['givenname'][0],
                'lastName'  => $result['sn'][0],
                'email'     => isset($result['mail'][0]) ? $result['mail'][0] : '',
            ];
        }

        if (!in_array($this->ldapService->errno(), [0, 4], true)) {
            $data = ['count' => 0];
            if ($this->debug) {
                $data['errno'] = $this->ldapService->errno();
                $data['error'] = $this->ldapService->error();
            }
            return new JsonResponse($data);
        }
        return new JsonResponse($data);
    }
}
