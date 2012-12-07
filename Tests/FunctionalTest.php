<?php

namespace Liip\SoapRecorderBundle\Tests;

use Liip\SoapRecorderBundle\Client\RecordableSoapClient;


class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * To understand this three static methods, please read the test README
     */
    const NORMAL_DECLARATION = 'class RecordableSoapClient extends \SoapClient';
    const TEST_DECLARATION = 'class RecordableSoapClient extends \Liip\SoapRecorderBundle\Tests\StandAloneSoapClient';
    public static function setUpBeforeClass()
    {
        self::updateRecordableSoapClientDeclaration(self::NORMAL_DECLARATION, self::TEST_DECLARATION);
    }
    public static function tearDownAfterClass()
    {
        self::updateRecordableSoapClientDeclaration(self::TEST_DECLARATION, self::NORMAL_DECLARATION);
    }
    public static function updateRecordableSoapClientDeclaration($from, $to)
    {
        $file = __DIR__.'/../Client/RecordableSoapClient.php';
        $newContent = str_replace($from, $to, file_get_contents($file));
        file_put_contents($file, $newContent);
    }

    /**
     * Ensure that the test setup have worked properly (see README)
     */
    public function testThatTheSetupIsWorking()
    {
        $client = new TestClient();
        $this->assertEquals('Liip\SoapRecorderBundle\Client\RecordableSoapClient', $parent = get_parent_class($client));
        $this->assertEquals('Liip\SoapRecorderBundle\Tests\StandAloneSoapClient', $parent = get_parent_class($parent));
        $this->assertEquals('SoapClient', get_parent_class($parent));
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRecordingWithoutFolders()
    {
        RecordableSoapClient::startRecording();
    }


    public function testSOAPCallWithoutInteraction()
    {
        TestServer::$fruit = 'pear';
        TestServer::$number = 17;

        $client = new TestClient();

        $this->assertEquals('pear', $client->getTheFruit());
        $this->assertEquals(17, $client->getTheNumber());
    }


    public function testRecording()
    {
        // Init
        $folders = $this->createAndConfigureTestFolders();
        RecordableSoapClient::startRecording();

        // Calling the webservice
        $client = new TestClient();
        $client->getTheFruit();

        // Check records
        $this->assertCount(3, $files = scandir($folders['request']));
        $this->assertCount(3, scandir($folders['response']));
        $this->assertCount(2, scandir($folders['wsdl']));
    }


    public function testFetchingLocalFirst()
    {
        // Init
        $this->createAndConfigureTestFolders();
        RecordableSoapClient::startRecording();
        TestServer::$fruit = 'apple';

        // Calling the webservice
        $client = new TestClient();
        $fruit = $client->getTheFruit();

        // Change the fruit and replay with local first
        RecordableSoapClient::setFetchingMode(RecordableSoapClient::FETCHING_LOCAL_FIRST);
        TestServer::$fruit = 'banana';
        $this->assertEquals('apple', $client->getTheFruit(), "Fetching the value from the records");
        $this->assertEquals(17, $client->getTheNumber(), "Ensure that the remote fallback is still working");

        // Switch to remote and try to get the banana
        RecordableSoapClient::setFetchingMode(RecordableSoapClient::FETCHING_REMOTE);
        $this->assertEquals('banana', $client->getTheFruit());
    }


    public function testFetchingLocalOnly()
    {
        $this->createAndConfigureTestFolders();
        RecordableSoapClient::startRecording();
        TestServer::$fruit = 'apple';

        // Calling the webservice
        $client = new TestClient();
        $fruit = $client->getTheFruit();

        // Change the fruit and replay with local first
        TestServer::$fruit = 'raspberry';
        RecordableSoapClient::setFetchingMode(RecordableSoapClient::FETCHING_LOCAL_ONLY);
        $this->assertEquals('apple', $client->getTheFruit());
    }


    /**
     * @expectedException RuntimeException
     */
    public function testFetchingLocalOnlyWithNoRecord()
    {
        $this->createAndConfigureTestFolders();
        RecordableSoapClient::setFetchingMode(RecordableSoapClient::FETCHING_LOCAL_ONLY);
        TestServer::$fruit = '';

        $client = new TestClient();
        $client->getTheFruit();
    }


    /**
     * Create three temporary folders to store records and configure them on the RecordableSoapClient
     *
     * @return array
     */
    protected function createAndConfigureTestFolders(){

        // Create the root temp folder
        $tempDir = tempnam(sys_get_temp_dir(),'');
        if (file_exists($tempDir)) {
            unlink($tempDir);
        }
        mkdir($tempDir);

        // Create sub folders
        $folders = array();
        foreach (array('request', 'response', 'wsdl') as $name){
            $folder = $tempDir.'/'.$name;
            mkdir($folder);
            $folders[$name] = $folder;
        }

        // Configure and return
        RecordableSoapClient::setRecordFolders($folders['request'], $folders['response'], $folders['wsdl']);
        return $folders;
    }

}
