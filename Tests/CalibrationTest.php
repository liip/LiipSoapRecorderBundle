<?php

namespace Liip\SoapRecorderBundle\Tests;

/**
 * This class is used to test that the SOAP Server in working without the recorder
 */
class CalibrationTest extends \PHPUnit_Framework_TestCase
{
    public function testServerFetching()
    {
        TestServer::$fruit = 'apple';
        TestServer::$number = -3;

        $client = new StandAloneSoapClient(null, array('uri'=>'', 'location'=>''));
        $this->assertEquals('apple', $client->getTheFruit());
        $this->assertEquals(-3 , $client->getTheNumber());
    }
}
