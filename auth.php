<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

class auth extends \PMVC\PlugIn
{
    public function init()
    {
        $this->loadClass('Logger');
        $this->loadClass('ProviderModel');
    }

    public function loadClass(string $className)
    {
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            \PMVC\l(__DIR__.'/src/'.$className.'.php');
        }
    }

    public function getProvider($ProviderName,$config)
    {
        $className = ucfirst($ProviderName).'Provider';
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            $file = __DIR__.'/src/providers/'.$className.'.php';
            \PMVC\l($file);
        }
        $class = __NAMESPACE__.'\\'.$className;
        return new $class($ProviderName, $config);
    }

    public function Login()
    {
        $config = $this->fb();
        $provider = $this->getProvider('facebook',$config['providers']['Facebook']);
        var_dump($provider);
    }

    public function Logout()
    {

    }

    public function fb()
    {
        $config = array(
              "base_url" => "http://devel.cometw.com/199nt/index.php/auth",
              "providers" => array (
                "Facebook" => array (
                  "enabled" => true,
                  "keys"    => array (
                    "id" => \PMVC\getOption('FB_APP_ID'),
                    "secret" => \PMVC\getOption('FB_APP_SECRET'),
                  ),
                  "scope"   => "email, user_about_me, user_birthday, user_hometown", // optional
                  "display" => "popup" // optional
        )));
        return $config;
    }
}

