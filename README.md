LiipSoapRecorderBundle [![Build Status](https://secure.travis-ci.org/liip/LiipSoapRecorderBundle.png?branch=master)](https://travis-ci.org/liip/LiipSoapRecorderBundle)
======================

This bundle provide an easy way to record SOAP communications. Typical usage could be:

 * Generating a set of fixtures for functional test writing
 * Recording a scenario and being able to replay it
 * Mocking the webservice to work offline
 * ...

Installation
------------

 1. Install this bundle like any other SF2 bundle (Composer or git submodule install + Enable it in the kernel)
 1. Replace the base class SoapClient by the new Liip\SoapRecorderBundle\Client\RecordableSoapClient


Configuration
-------------

By default the bundle does nothing, to activate it, you just need to configure it:

```
liip_soap_recorder:
    record:          true                 # boolean, activate or not the recording
    fetching_mode:   local_first          # can be remote, local_first or local_only
    request_folder:  /tmp/soap_request    # where to store the XML request
    response_folder: /tmp/soap_response   # where to store the XML response
    wsdl_folder:     /tml/soap_wsdl       # where to store the WSDL of the webservice
    enable_profiler: true                 # boolean, active or not the profiler
    die_on_error:    false
```

Usage
-----

To use the bundle, you can play with some config parameters:

 * **record** can be set to
   * *true*: to start communication recording
   * *false*: to stop it
 * **fetching_mode** can be set to:
   * *remote*: Always fetch response from the WebService
   * *local_only*: Always fetch response from the local recording
   * *local_first*: Try to fetch locally, and if not recorded yet, fetch to the WebService
 * **enable_profiler** can be set to:
   * *true*: to display SOAP records in the Symfony2 Profiler. It will delete the recorded files from the directories.
   * *false*: to keep the files in the directories without using the Symfony2 Profiler.
 * **die_on_error** can be used to define the behaviour in case you are in local_only and a record is missing:
  * false:  Normal behavior, will throw an exception
  * true:   Will die() with an explicit message, this is useful on Symfony2 where sometimes the
            generated exception is replaced by an AccessDeniedException which masked the original one




Usage outside Symfony2
----------------------

The heart of the bundle is the class Liip\SoapRecorderBundle\Client\RecordableSoapClient. This class is
 independent, so you can use it outside of the Bundle, in any PHP 5.2 project:

 1. Replace your base class SoapClient by the new Liip\SoapRecorderBundle\Client\RecordableSoapClient
 1. Start recording by calling:

```
   RecordableSoapClient::setRecordFolders('/tmp/request', '/tmp/response', '/tmp/wsdl');
   RecordableSoapClient::startRecording();
   // Call your webservice like usual`
```

1. Start playing your records

```
   RecordableSoapClient::setFetchingMode(RecordableSoapClient::FETCHING_LOCAL_FIRST);
   // Call your webservice like usual
```


Contributing
------------
If you would like to contribute, just go on the project page: https://github.com/liip/LiipSoapRecorderBundle, fork it
and providing PRs.

This project comes with a functional test suite, just read the Tests/README.md for more information.

Travis CI is also running for continuous integration tests: [![Build Status](https://secure.travis-ci.org/liip/LiipSoapRecorderBundle.png?branch=master)](https://travis-ci.org/liip/LiipSoapRecorderBundle)

Requirements
------------

PHP 5.2


Authors
-------

- Pierre Vanhulst - Liip SA <pierre.vanhulst@liip.ch>
- David Jeanmonod - Liip SA <david.jeanmonod@liip.ch>


License
-------

LiipSoapRecorderBundle is licensed under the MIT License - see the LICENSE file for details
