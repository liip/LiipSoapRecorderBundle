<?php

namespace Liip\SoapRecorderBundle\Tests;

use Liip\SoapRecorderBundle\Tests\Helpers\TestServer;
use Liip\SoapRecorderBundle\Tests\Helpers\LocalSoapClient as CalibrationClient;

/**
 * This class is used to test that the SOAP Server in working without the recorder
 *
 * @author David Jeanmonod <david.jeanmonond@liip.ch>
 */
class CalibrationTest extends \PHPUnit_Framework_TestCase
{
    public function testServerFetching()
    {
        TestServer::$fruit = 'apple';
        TestServer::$number = -3;

        $client = new CalibrationClient(null, array('uri'=>'', 'location'=>''));
        $this->assertEquals('apple', $client->getTheFruit());
        $this->assertEquals(-3 , $client->getTheNumber());
    }
}
