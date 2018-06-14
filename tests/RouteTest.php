<?php

use EasyRoute\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testIsThereAnySyntaxError()
    {
        $object = new Route();
        $this->assertTrue(is_object($object));
        unset($object);
    }

}
