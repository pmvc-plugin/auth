<?php
namespace PMVC\PlugIn\auth;

use PHPUnit_Framework_TestCase;

\PMVC\Load::plug();
\PMVC\addPlugInFolders(['../']);
\PMVC\plug('session', ['disableStart'=>true]);

class AuthTest extends PHPUnit_Framework_TestCase
{
    private $_plug = 'auth';

    function setup()
    {
    }

    function testPlugin()
    {
        ob_start();
        print_r(\PMVC\plug($this->_plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($this->_plug,$output);
    }

    function testHashIsAuth()
    {
        $p = \PMVC\plug($this->_plug);
        $hash = $p->hashIsAuth('foo', 'bar');
        $this->assertEquals('ba4TuD1iozTxw', $hash);
    }

   /**
    * @runInSeparateProcess
    */
    function testSetIsAuthorized()
    {
        $p = \PMVC\plug($this->_plug);
        $p->setIsLogin(); 
        $store = $p['store'];
        $this->assertNotNull($store['authKey']);
        $this->assertNotNull($store['authHash']);
    }

   /**
    * @runInSeparateProcess
    */
   function testIsLogin()
   {
        $p = \PMVC\plug($this->_plug);
        $_COOKIE[$p['bcookie']] = 'fakeB';
        $privateKey = $p->setIsLogin(); 
        $store = $p['store'];
        $_COOKIE[$store['authKey']] = $privateKey;
        $result = $p->isLogin();
        $this->assertTrue($result);
   }
}
