LiipSoapRecorderBundle Test
===========================

Testing this tool was not easy. The goal of those tests was to allow testing without having to use the HTTP Layer.

At the time of writing, mocking a parent method of an object was not possible in PHPUnit, so there is not easy way to
 mock the SoapClient::__doRequest().
This have been done with a specific class LocalSoapClient, this class extend SoapClient, but instead of sending
 requests on the wire, it resolve them locally using the TestServer class. This two classes are tested into the
 CalibrationTest.

Then, in order to use that class in tests, it require that we modify the RecordableSoapClient to extends this new class
 instead of the base SoapClient. This is done in the PHPUnit Setup method. So before the Setup, the class chain is like
 that:

   YourSoapClient > RecordableSoapClient > SoapClient >>> call to a remote service

And after the setup:

   YourSoapClient > RecordableSoapClient > LocalSoapClient > SoapClient >>> resolve call locally with TestServer

Everything is put back in the place in the tearDown method.


Running tests
-------------

The SoapServer class is setting HTTP headers when handling a call. This result in a conflict with PHPUnit:

```
Cannot modify header information - headers already sent by (output started at /usr/local/php5-20111115-115202/lib/php/PHPUnit/Util/Printer.php:173)
```

To workaround this, just redirect the PHPUnit output to the STDERR.

```
   phpunit --stderr
```

Continuous integration
----------------------

Travis CI is there: [![Build Status](https://secure.travis-ci.org/liip/LiipSoapRecorderBundle.png?branch=master)](https://travis-ci.org/liip/LiipSoapRecorderBundle)


Contributing
------------
If you would like to contribute, just go on the project page: https://github.com/liip/LiipSoapRecorderBundle