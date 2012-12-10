<?php

namespace Liip\SoapRecorderBundle\Tests\Helpers;

class TestClient extends \Liip\SoapRecorderBundle\Client\RecordableSoapClient
{
    public function __construct($wsdl = null)
    {
        $options = isset($wsdl) ? array() : array('location'=>'','uri'=>'');
        parent::__construct($wsdl, $options);
    }
}