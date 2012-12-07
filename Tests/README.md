LiipSoapRecorderBundle Test
===========================

Testing this tool is not easy. The goal of those tests was to allow testing without having to use the HTTP Layer.

This have been done with a specific class StandAloneSoapClient, this class extand SoapClient, but instead of sending
 requests on the wire, it resolve them locally using a TestServer class. This two classes are tested into the
 CalibrationTest.

Then, in order to use that class in tests, it require that we modify the RecordableSoapClient to extends this new class
 instead of the base SoapClient. This is done in the PHPUnit Setup method. So before the Setup, the class chain is like
 that:

   YourSoapClient > RecordableSoapClient > SoapClient >>> call to a remote service

And after the setup:

   YourSoapClient > RecordableSoapClient > StandAloneSoapClient > SoapClient >>> resolve call locally with TestServer


Contributing
------------
If you would like to contribute, just go on the project page: https://github.com/liip/LiipSoapRecorderBundle