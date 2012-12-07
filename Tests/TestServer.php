<?php

namespace Liip\SoapRecorderBundle\Tests;

class TestServer
{
    public static $fruit = null;
    public static $number = null;

    public function getTheFruit()
    {
        return self::$fruit;
    }

    public function getTheNumber()
    {
        return self::$number;
    }
}