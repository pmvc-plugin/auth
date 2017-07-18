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
        \PMVC\unplug($this->_plug);
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
        $_COOKIE[$p['bcookie']] = 'fakeB';
        $p->setIsAuth(); 
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
        $privateKey = $p->setIsAuth(); 
        $store = $p['store'];
        $_COOKIE[$store['authKey']] = $privateKey;
        $result = $p->isAuth();
        $this->assertTrue($result);
   }

   /**
    * @runInSeparateProcess
    */
   function testIsExpire()
   {
        $p = \PMVC\plug($this->_plug);
        $_COOKIE[$p['bcookie']] = 'fakeB';
        $privateKey = $p->setIsAuth(); 
        $store = $p['store'];
        $_COOKIE[$store['authKey']] = $privateKey;
        $p['lifetime'] = 100;
        $this->assertFalse($p->isExpire());
        $p['lifetime'] = -1;
        $this->assertTrue($p->isExpire());
   }
}
