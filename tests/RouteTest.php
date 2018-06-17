<?php

use EasyRoute\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $_SERVER['REQUEST_URI'] = '/home/user/folder/tests/test?query=98';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SCRIPT_NAME'] = '/home/user/folder/tests/RouteTest.php';

        $file = fopen("test.php", "w");
        fwrite($file, "<?php echo 'hey'; ?>");
        fclose($file);

        $file = fopen("value.php", "w");
        fwrite($file, "<?php echo \$param; ?>");
        fclose($file);
    }

    public static function tearDownAfterClass()
    {
        unlink("test.php");
        unlink("value.php");
    }

    public function testIsThereAnySyntaxError()
    {
        $object = new Route();
        $this->assertTrue(is_object($object));
        unset($object);
    }

    public function testFileMatch()
    {
        $this->expectOutputString("hey");
        try {
            $route = new Route();
            $route->addMatch('GET', '/test', 'test.php');
            $route->execute();
        } catch (Exception $exception) {
            echo($exception->getMessage());
        }
    }

    public function testCallableMatch()
    {
        $this->expectOutputString("hey");
        try {
            $route = new Route();
            $route->addMatch('GET', '/test', function () {
                echo 'hey';
            });
            $route->execute();
        } catch (Exception $exception) {
            echo($exception->getMessage());
        }
    }

    public function testFileNotFound()
    {
        $this->expectOutputString('File error.php not found.');
        try {
            $route = new Route();
            $route->addMatch('GET', '/test', 'error.php');
            $route->execute();
        } catch (Exception $exception) {
            echo($exception->getMessage());
        }
    }

    /*public function testValuePass()
    {
        $this->expectOutputString('Perfect');
        try {
            $param = 'Perfect';
            $route = new Route();
            $route->addMatch('GET', '/test', 'value.php');
            $route->execute();
        } catch (Exception $exception) {
            echo($exception->getMessage());
        }
    }*/

}
