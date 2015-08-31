<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

class auth extends \PMVC\PlugIn
{
    public function init()
    {
        $this->initSession();
    }

    public function loadClass(string $className)
    {
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            \PMVC\l(__DIR__.'/src/'.$className.'.php');
        }
    }

    public function initProvider($ProviderName,$config)
    {
        $className = ucfirst($ProviderName).'Provider';
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            $this->loadClass('Logger');
            $this->loadClass('User');
            $this->loadClass('ProviderModel');
            $file = __DIR__.'/src/providers/'.$className.'.php';
            \PMVC\l($file);
        }
        $class = __NAMESPACE__.'\\'.$className;
        return new $class($ProviderName, $config, $this['storage']);
    }

    public function getProvider()
    {
        $config = $this->fb();
        $provider = $this->initProvider('facebook',$config['providers']['Facebook']);
        return $provider;
    }

    public function initSession()
    {
        \PMVC\plug('guid')->getDb('session');
        $session_key = 'PMVC_AUTHENTICATION';
        if (!isset($_SESSION[$session_key])) {
            $_SESSION[$session_key] = new \PMVC\HashMap();
        }
        $this['storage'] = $_SESSION[$session_key];
    }


    public function login()
    {
        $config = $this->fb();
        $provider = $this->initProvider('facebook',$config['providers']['Facebook']);
        $provider->endpoint = 'http://devel.cometw.com/199nt/index.php/auth/success';
        return $provider->loginBegin();
    }

    public function loginBack($request)
    {
        $config = $this->fb();
        $provider = $this->initProvider('facebook',$config['providers']['Facebook']);
        $provider->loginFinish($request);
        return $provider;
    }


    public function logout()
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
                    "id" => \PMVC\getOption('FACEBOOK_APP_ID'),
                    "secret" => \PMVC\getOption('FACEBOOK_APP_SECRET'),
                  ),
                  "scope"   => "email, user_about_me, user_birthday, user_hometown", // optional
                  "display" => "popup" // optional
        )));
        return $config;
    }
}

