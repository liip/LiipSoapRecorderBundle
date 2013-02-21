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
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // If the profiler is disable, just return
        if($this->config['enable_profiler'] !== true) {
            return;
        }

        $requests = $this->fetchSOAPRecordsFromFolder($this->config['request_folder']);
        $responses = $this->fetchSOAPRecordsFromFolder($this->config['response_folder']);

        $this->data = array(
            'requests'  => $requests,
            'responses' => $responses,
            'count'    => count($requests),
        );
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

            // Ignore sub folders and hidden files
            if( is_dir($file) || substr($file, 0, 1) === '.') {
                continue;
            }

            $fileContent = file_get_contents($folder.'/'.$file);

            // XML Formatting
            if (strlen($fileContent) > 0){
                $doc = new \DOMDocument;
                $doc->loadXML($fileContent, LIBXML_NOERROR);
                $doc->formatOutput = TRUE;
                $fileContent = $doc->saveXML();
            }

            // Saved and remove the original file
            $fileList[] = $fileContent;
            unlink($folder.'/'.$file);
        }

        return $fileList;
    }
}