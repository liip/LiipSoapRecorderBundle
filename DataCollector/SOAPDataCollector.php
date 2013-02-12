<?php

namespace Liip\SoapRecorderBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SOAPDataCollector extends DataCollector
{

    protected $config;
	
    public function __construct($container)
    {
        $this->config = $container->getParameter('liip_soap_recorder_config');
    }

    /**
     * {@inheritdoc}
     *
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    	$requests = $this->fetchSOAPRecordsFromFolder($this->config['request_folder']);
    	$responses = $this->fetchSOAPRecordsFromFolder($this->config['response_folder']);

        if($this->config['enable_profiler']) {
            $this->data = array(
                'requests'  => $requests,
                'responses' => $responses,
                'count'    => count($requests),
            );
		
	        $this->emptyFolders(array($this->config['request_folder'], $this->config['response_folder']));
	    }
    }

    /**
     * Returns the collector name.
     *
     * @return string   The collector name.
     */
    public function getName()
    {
        return 'soap';
    }

    /**
     * Returns the number of recorded SOAP calls, previously.
     * recorded by getRequestCount() method.
     *
     * @return int   The number of recorded calls.
     */
    public function getCount()
    {
        return $this->data['count'];
    }

    /**
     * Returns the recorded requests, previously.
     * recorded by fetchSOAP() method.
     *
     * @return array   Array containing all recorded requests.
     */
    public function getRequests()
    {
        return $this->data['requests'];
    }

    /**
     * Returns the recorder responses, previously.
     * recorded by fetchSOAP() method.
     *
     * @return int   Array containing all recorded responses.
     */
    public function getResponses()
    {
        return $this->data['responses'];
    }

    /**
     * Fetch the content of all files inside a folder.
     *
     * @return array  An array of files
     */
    protected function fetchSOAPRecordsFromFolder($folder)
    {
        $fileList = array();
        foreach(scandir($folder) as $file) {
            if(substr($file, 0, 1) != '.') {
                $fileContent = file_get_contents($folder.'/'.$file);

                $doc = new \DOMDocument;
                $doc->loadXML($fileContent, LIBXML_NOERROR);
                $doc->formatOutput = TRUE;
     	        $fileContentFormatted = $doc->saveXML();

                array_push($fileList, $fileContentFormatted);
            }
        }
        return $fileList;
    }

    /**
     * Delete files within each array of a given folder.
     *
     * @return null
     */
    protected function emptyFolders(array $folders)
    {
        foreach($folders as $folder) {
            foreach(scandir($folder) as $file) {
                if(substr($file, 0, 1) != '.') {
                    unlink($folder.'/'.$file);
                }
            }
        }
    }
}