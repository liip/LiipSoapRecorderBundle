<?php

namespace Liip\SoapRecorderBundle\Client;

/**
 * This class allow to record SOAP communication. Just extends it instead of the classic
 *  SoapClient, and then configure it with the public static methods available
 *
 * @author David Jeanmonod <david.jeanmonod@liip.ch>
 */
class RecordableSoapClient extends \SoapClient
{
    const FETCHING_LOCAL_ONLY  = 'local_only';
    const FETCHING_LOCAL_FIRST = 'local_first';
    const FETCHING_REMOTE      = 'remote';

    protected static $recordCommunications = false;
    protected static $fetchingMode = self::FETCHING_REMOTE;
    protected static $requestFolder = null;
    protected static $responseFolder = null;
    protected static $wsdlFolder = null;

    /**
     * @var string This will be the unique identifier that will be used to identify a request
     */
    protected $uniqueRequestId = null;
    protected $wsdlUrl = null;


    /**
     * Override of the default constructor, this allow to save the WSDL and re-use it in local_only mode
     *
     * @param string  $wsdlUrl
     * @param array   $options
     */
    public function __construct($wsdlUrl, $options)
    {
        $this->wsdlUrl = $wsdlUrl;

        // WSDL recording
        if (self::$recordCommunications == true) {
            $this->recordWsdlIfRequired($wsdlUrl, $options);
        }

        // On local only mode, we have to use the recorded wsdl
        if (self::$fetchingMode === self::FETCHING_LOCAL_ONLY && $wsdlUrl !== null) {
            if (!file_exists($wsdlFile = $this->getWsdlFilePath($wsdlUrl))){
                throw new \RuntimeException("Impossible to find a recorded WSDL $wsdlFile");
            }
            return parent::__construct($wsdlFile, $options);
        }
        
        parent::__construct($wsdlUrl, $options);
    }


    /**
     * Record the provided WSDl if not yet exist
     * @param $wsdlUrl
     * @param $options
     */
    protected function recordWsdlIfRequired($wsdlUrl, $options)
    {
        if ($wsdlUrl!== null) {
            $wsdlFile = $this->getWsdlFilePath($wsdlUrl);
            if (!file_exists($wsdlFile)) {
                file_put_contents($wsdlFile, self::formatXml(file_get_contents($wsdlUrl)));
            }
        }
    }


    /**
     * Start the recording, RecordableSoapClient::setRecordFolders() must be called before
     */
    public static function startRecording()
    {
        if (self::$fetchingMode === self::FETCHING_LOCAL_ONLY) {
            throw new \Exception("It's not possible to record with FETCHING_LOCAL_ONLY mode");
        }
        foreach (array(self::$requestFolder, self::$responseFolder, self::$wsdlFolder) as $folder){
            if ($folder===null){
                throw new \InvalidArgumentException("You must call RecordableSoapClient::setRecordFolders() before recording");
            }
            if (!is_writable($folder)) {
                throw new \InvalidArgumentException("The folder [$folder] have not write permissions");
            }
        }

        self::$recordCommunications = true;
    }


    /**
     * Stop the recording
     */
    public static function stopRecording()
    {
        self::$recordCommunications = false;
    }

    /**
     * Configure the three folders where communications will be recorded
     *
     * @param string $requestFolder
     * @param string $responseFolder
     * @param string $wsdlFolder
     */
    public static function setRecordFolders($requestFolder, $responseFolder, $wsdlFolder)
    {
        // Validity check
        foreach (array($requestFolder, $responseFolder, $wsdlFolder) as $folder){
            if (!file_exists($folder)) {
                throw new \InvalidArgumentException("The provided folder [$folder] doesn't exist");
            }
        }

        // Normalize the folders name
        self::$requestFolder = realpath($requestFolder);
        self::$responseFolder = realpath($responseFolder);
        self::$wsdlFolder = realpath($wsdlFolder);
    }


    /**
     * Select the mode that will be used for webservice response fetching
     * If mode FETCHING_LOCAL_* RecordableSoapClient::setRecordFolders() must also be called
     *
     * @param $fetchingMode
     * @throws \InvalidArgumentException
     */
    public static function setFetchingMode($fetchingMode)
    {
        // Check mode validity
        if (!in_array($fetchingMode, array(self::FETCHING_LOCAL_FIRST, self::FETCHING_LOCAL_ONLY, self::FETCHING_REMOTE))) {
            throw new \InvalidArgumentException("You must set the fetching mode with one of the provided constants");
        }

        // Check folders are set
        if ($fetchingMode !== self::FETCHING_REMOTE) {
            foreach (array(self::$requestFolder, self::$responseFolder, self::$wsdlFolder) as $folder) {
                if ($folder===null) {
                    throw new \InvalidArgumentException("You must call RecordableSoapClient::setRecordFolders() before fetching local");
                }
            }
        }

        self::$fetchingMode = $fetchingMode;
    }


    /**
     * This method is overridden to generate a unique request ID based on the function name and arguments
     * The id is generated here, as it's more easy to work on those high level parameters than on the XML
     * of the __doRequest method
     *
     * @param string $function_name
     * @param array $arguments
     */
    public function __call($function_name, $arguments)
    {
        $this->populateTheUniqueRequestIdIfRequired($function_name, $arguments);
        return parent::__call($function_name, $arguments);
    }


    /**
     * This method is overrided to generate a unique request ID based on the function name and arguments
     *
     * @see self::__call()
     */
    public function __soapCall ($function_name, $arguments, $options=null, $input_headers=null, &$output_headers=null)
    {
        $this->populateTheUniqueRequestIdIfRequired($function_name, $arguments);
        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }


    /**
     * Fill up the request id, if we need it, by generating it with the method generateUniqueRequestId
     *
     * @param $functionName
     * @param $arguments
     */
    protected function populateTheUniqueRequestIdIfRequired($functionName, $arguments)
    {
        if (self::$fetchingMode !== self::FETCHING_REMOTE || self::$recordCommunications){
            $this->uniqueRequestId = $this->generateUniqueRequestId($this->wsdlUrl, $functionName, $arguments);
        }
    }

    /**
     * Generation of a unique request id, based on high level parameters (function name and arguments).
     * This function can be overridden to use a different file naming, or to ignore some dynamic parameters
     *  like date
     *
     * @param $wsdlUrl       string
     * @param $functionName  string
     * @param $arguments     array
     * @return string
     */
    public function generateUniqueRequestId($wsdlUrl, $functionName, $arguments)
    {
        return md5($functionName.serialize($arguments));
    }


    /**
     * Override the do request in order to record communications and/or to fetch response from the local
     *  filesystem
     *
     * @throws \RuntimeException   only in LOCAL_ONLY mode
     */
    public function __doRequest ($request, $location, $action, $version, $one_way = 0)
    {
        // Handle local request fetching
        if (self::$fetchingMode === self::FETCHING_LOCAL_ONLY || self::$fetchingMode === self::FETCHING_LOCAL_FIRST) {
            if (file_exists($this->getResponseFilePath())){
                return file_get_contents($this->getResponseFilePath());
            }
            elseif (self::$fetchingMode === self::FETCHING_LOCAL_ONLY) {
                throw new \RuntimeException("Impossible to find a recorded SOAP response for the following request:\n$request");
            }
        }

        // Potentially record the request
        if (self::$recordCommunications) {
            file_put_contents($this->getRequestFilePath(), self::formatXml($request));
        }

        // Process the real SOAP call
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        // Potentially record the response
        if (self::$recordCommunications) {
            file_put_contents($this->getResponseFilePath(), self::formatXml($response, false));
        }

        return $response;
    }


    /**
     * Generate a request file path based on the unique id
     *
     * @return string
     */
    protected function getRequestFilePath()
    {
        return $this->generateFilePath('request');
    }


    /**
     * Generate a response file path based on the unique id
     *
     * @return string
     */
    protected function getResponseFilePath()
    {
        return $this->generateFilePath('response');
    }


    /**
     * Generate a path where to store a given WSDL
     *
     * @return string
     */
    protected function getWsdlFilePath($wsdlUrl)
    {
        return self::$wsdlFolder.DIRECTORY_SEPARATOR.basename($wsdlUrl);
    }


    /**
     * Return a record (request or response) file path according to the uniqueRequestId
     *
     * @param $type
     * @return string
     * @throws \RuntimeException
     */
    protected function generateFilePath($type)
    {
        $folder = $type==='request' ? self::$requestFolder : self::$responseFolder;
        if ($folder === null) {
            throw new \RuntimeException("You must call RecordableSoapClient::setRecordFolders() before using the recorder");
        }
        if ($this->uniqueRequestId === null){
            throw new \RuntimeException("Unexpected error when generating the unique request ID, please contact the LiipSoapRecorderBundle maintainers");
        }

        return $folder.DIRECTORY_SEPARATOR.$this->uniqueRequestId.'.xml';
    }


    /**
     * Format XML input in a more readable fashion
     *
     * @param $xmlData           string
     * @param $includeXmlHeader  boolean
     * @return string
     */
    public function formatXml($xmlData, $includeXmlHeader = true)
    {
    	if(isset($xmlData)) {
        	$doc = new \DOMDocument;
            $doc->loadXML($xmlData, LIBXML_NOERROR);
            $doc->formatOutput = TRUE;
        
            if(!$includeXmlHeader) {
                foreach($doc->childNodes as $node) {
                    $xmlOutput = $doc->saveXML($node);
                }
            }
            else {
                $xmlOutput = $doc->saveXML();
            }

            return $xmlOutput;
    	}
    	else {
    	    return $xmlData;
    	}
    }
}
