<?php

namespace Liip\SoapRecorderBundle\Tests;

class TestClient extends \Liip\SoapRecorderBundle\Client\RecordableSoapClient
{
    public function __construct($wsdl = null)
    {
        $options = isset($wsdl) ? array() : array('location'=>'test','uri'=>'http://test.org');
        parent::__construct($wsdl, $options);
    }
}