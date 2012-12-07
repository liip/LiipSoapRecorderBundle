<?php

namespace Liip\SoapRecorderBundle\Client;

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
    protected $uniqueRequestId = null;

    /**
     * Override the default constructor to save WSDL if we are in recoding mode and to use it in local_only mode
     * @param string $wsdlUrl
     * @param array $options
     */
    public function __construct($wsdlUrl, $options)
    {
        // WSDL recording
        if (self::$recordCommunications == true) {
            $this->recordWsdlIfRequire($wsdlUrl, $options);
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

    protected function recordWsdlIfRequire($wsdlUrl, $options)
    {
        if ($wsdlUrl!== null) {
            $wsdlFile = $this->getWsdlFilePath($wsdlUrl);
            if (!file_exists($wsdlFile)) {
                file_put_contents($wsdlFile, file_get_contents($wsdlUrl));
            }
        }
    }


    /**
     * Start the recording mode, RecordableSoapClient::setRecordFolders() must be called before
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
     * Stop the recording mode
     */
    public static function stopRecording()
    {
        self::$recordCommunications = false;
    }

    /**
     * Configure the three folders where communications will be recorded
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
     * @param $fetchingMode
     * @throws \InvalidArgumentException
     */
    public static function setFetchingMode($fetchingMode)
    {
        if (!in_array($fetchingMode, array(self::FETCHING_LOCAL_FIRST, self::FETCHING_LOCAL_ONLY, self::FETCHING_REMOTE))) {
            throw new \InvalidArgumentException("You must set the fetching mode with one of the provided constants");
        }
        self::$fetchingMode = $fetchingMode;
    }

    /**
     * This method is overrided to generate a unique request ID based on the function name and arguments
     */
    public function __call($function_name, $arguments)
    {
        $this->populateTheUniqueRequestIdIfRequire($function_name, $arguments);
        return parent::__call($function_name, $arguments);
    }

    /**
     * This method is overrided to generate a unique request ID based on the function name and arguments
     */
    public function __soapCall ($function_name, $arguments, $options=null, $input_headers=null, &$output_headers=null)
    {
        $this->populateTheUniqueRequestIdIfRequire($function_name, $arguments);
        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }

    /**
     * Generation of a unique request id, this have to be done with high level parameters (function name and arguments).
     *  Trying to do it with the SOAP request in the __doRequest method will fail as the XML SOAP request is different
     *  in WSDL mode and non-WSDL mode
     *
     * @param $functionName
     * @param $arguments
     */
    protected function populateTheUniqueRequestIdIfRequire($functionName, $arguments)
    {
        if (self::$fetchingMode !== self::FETCHING_REMOTE || self::$recordCommunications){
            $this->uniqueRequestId = md5($functionName.serialize($arguments));
        }
    }

    /**
     * Override the do request in order to record communications and/or to fetch response from the local
     *  filesystem
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

        // Process the real SOAP call
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        // Potentially record the call
        if (self::$recordCommunications) {
            file_put_contents($this->getRequestFilePath(), $request);
            file_put_contents($this->getResponseFilePath(), $response);
        }

        return $response;
    }

    protected function getRequestFilePath()
    {
        return $this->generateFilePath('request');
    }

    protected function getResponseFilePath()
    {
        return $this->generateFilePath('response');
    }

    protected function getWsdlFilePath($wsdlUrl)
    {
        return self::$wsdlFolder.DIRECTORY_SEPARATOR.basename($wsdlUrl);
    }

    /**
     * Return a record file path according to the uniqueRequestId
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
            throw new \RuntimeException("You must call RecordableSoapClient::setRecordFolders() before using the recorder");
        }

        return $folder.DIRECTORY_SEPARATOR.$this->uniqueRequestId.'.xml';
    }
}