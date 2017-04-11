<?php
PMVC\Load::plug();
PMVC\addPlugInFolders(['../']);
class AuthTest extends PHPUnit_Framework_TestCase
{
    private $_plug = 'auth';

    function setup()
    {
        PMVC\plug('session', ['disableStart'=>true]);
    }

    function testPlugin()
    {
        ob_start();
        print_r(PMVC\plug($this->_plug));
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

}
