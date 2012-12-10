<?php

namespace Liip\SoapRecorderBundle\Tests\Helpers;

/**
 * Specific SOAP client that resolve SOAP request locally (see tests README for info)
 *
 * @author David Jeanmonod <david.jeanmonond@liip.ch>
 */
class LocalSoapClient extends \SoapClient
{
    protected $server;

    public function __construct($wsdl, $options)
    {
        // Create the client like normal
        parent::__construct($wsdl, $options);

        // Create a local serveur that will process the calls
        $this->server = new \SoapServer($wsdl, $options);
        $this->server->setClass('Liip\SoapRecorderBundle\Tests\Helpers\TestServer');
    }

    public function __doRequest ($request, $location, $action, $version, $one_way = 0)
    {
        // The call is process locally instead of using the real SoapClient __doRequest
        ob_start();
        $this->server->handle($request);
        $response = ob_get_contents();
        ob_end_clean();

        return $response;
    }
}