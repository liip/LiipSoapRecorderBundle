<?php

namespace Liip\SoapRecorderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Liip\SoapRecorderBundle\Client\RecordableSoapClient;

class LiipSoapRecorderBundle extends Bundle
{
    /**
     * Configure the recorder according to the container config
     */
    public function boot()
    {
        if ($this->container->hasParameter('liip_soap_recorder_config')) {
            $config = $this->container->getParameter('liip_soap_recorder_config');
            if ($config['record']===true) {
                RecordableSoapClient::startRecording();
            }
            if ($config['fetching_mode'] !== 'remote' || $config['record'] === true) {
                RecordableSoapClient::setFetchingMode($config['fetching_mode']);
                RecordableSoapClient::setRecordFolders(
                    $config['request_folder'],
                    $config['response_folder'],
                    $config['wsdl_folder']
                );
            }
        }
    }
}