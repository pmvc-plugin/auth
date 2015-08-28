<?php
namespace PMVC\PlugIn\auth;
use Hybridauth\Hybridauth;

 \PMVC\l(__DIR__.'../../');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

class auth extends \PMVC\PlugIn
{
    public function init()
    {
        $config = array(
              "base_url" => "http://mywebsite.com/path/to/hybridauth/",
              "providers" => array (
                "Facebook" => array (
                  "enabled" => true,
                  "keys"    => array ( "id" => "PUT_YOURS_HERE", "secret" => "PUT_YOURS_HERE" ),
                  "scope"   => "email, user_about_me, user_birthday, user_hometown", // optional
                  "display" => "popup" // optional
        )));
        $hybridauth = new Hybridauth( $config );
        $adapter = $hybridauth->authenticate( "Facebook" );     
        $user_profile = $adapter->getUserProfile();
        var_dump($hybridauth);
    }
}

