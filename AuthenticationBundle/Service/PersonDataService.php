<?php

namespace Kuleuven\AuthenticationBundle\Service;

class PersonDataService
{
    protected $url;

    public function __construct($url = 'https://webwsp.aps.kuleuven.be/esap/public/odata/sap/zh_person_srv/Persons(\'%s\')?$format=json&$expand=WorkAddresses')
    {
        $this->url = $url;
    }

    public function getPersonData($uid)
    {
        $url = sprintf($this->url, str_replace('u', '0', $uid));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if (false === $output) {
            throw new \Exception('Could not get person data for ' . $uid . ': ' . $error);
        }
        $decodedOutput = json_decode($output);
        if (null === $decodedOutput) {
            throw new \Exception('Could not get person data for ' . $uid . ': ' . json_last_error_msg());
        }
        if (0 === count($decodedOutput->d)) {
            throw new \Exception('Could not get person data for ' . $uid . ': ' . json_encode($decodedOutput));
        }
        $user = $decodedOutput->d;
        $mainWorkAddresses = array_filter($user->WorkAddresses->results, function ($value) {
            return $value->isMainWorkAddress;
        });
        $mainWorkAddress = reset($mainWorkAddresses);
        if (!empty($mainWorkAddress)) {
            $workAddress = [
                'phone'      => $mainWorkAddress->phone,
                'label'      => $mainWorkAddress->buildingDescription,
                'street'     => $mainWorkAddress->street,
                'number'     => $mainWorkAddress->houseNr,
                'postalCode' => $mainWorkAddress->postalCode,
                'city'       => $mainWorkAddress->city,
                'province'   => null,
                'country'    => null,
            ];
        } else {
            $workAddress = null;
        }
        $userData = [
            'username'               => $uid,
            'primaryEmail'           => $user->preferredMailAddress,
            'firstName'              => !empty($user->firstName) ? $user->firstName : $user->preferredName,
            'lastName'               => $user->surname,
            'title'                  => !empty($user->title) ? $user->title : null,
            'sexe'                   => $user->gender,
            'birthDate'              => null,
            'birthLocation'          => null,
            'birthCountry'           => null,
            'nationality'            => null,
            'correspondenceLanguage' => null,
            'picture'                => !empty($user->pictureUrl) ? $user->pictureUrl : null,
            'privateEmail'           => null,
            'mobilePhoneNumber'      => $user->mobilePhone,
            'workAddress'            => $workAddress,
        ];
        return $userData;
    }
}
