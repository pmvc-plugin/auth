<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

class AuthKey {
    const SESSION_KEY = 'pmvc_plugin_auth';
    const CURRENT_USER = 'current_user';
}


class auth extends \PMVC\PlugIn
{
    public function getKeys()
    {
        return new AuthKey();
    }

    public function init()
    {
        $this->initSession();
        $this['session_key'] = AuthKey::SESSION_KEY;
    }

    public function initSession()
    {
        $session_key = AuthKey::SESSION_KEY;
        if (!isset($_SESSION[$session_key])) {
            $_SESSION[$session_key] = new \PMVC\HashMap();
        }
        $this['storage'] = $_SESSION[$session_key];
    }

    public function loadClass(string $className)
    {
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            \PMVC\l(__DIR__.'/src/'.$className.'.php');
        }
    }

    public function initProvider($providerName,$config)
    {
        $className = ucfirst($providerName).'Provider';
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            $this->loadClass('Logger');
            $this->loadClass('ProviderModel');
            $file = __DIR__.'/src/providers/'.$className.'.php';
            \PMVC\l($file);
        }
        $class = __NAMESPACE__.'\\'.$className;
        return new $class($providerName, $config, $this['storage']);
    }

    public function getProvider($providerName)
    {
        $config = $this->getConfig($providerName);
        $provider = $this->initProvider($providerName,$config);
        return $provider;
    }

    public function login($providerName='facebook')
    {
        $config = $this->getConfig($providerName);
        $provider = $this->initProvider($providerName,$config);
        $provider->endpoint = $this['return'];
        return $provider->loginBegin();
    }

    public function loginReturn($request,$providerName='facebook')
    {
        $config = $this->getConfig($providerName);
        $provider = $this->initProvider($providerName,$config);
        $provider->loginFinish($request);
        return $provider;
    }

    public function logout()
    {

    }

    public function getConfig($providerName)
    {
        $dot = \PMVC\plug('dotenv');
        $fileName = '.env.auth_'.$providerName;
        return $dot->getArray($fileName);
    }

    public function oauthSign($url, $secret, $token=null)
    {
        if (!class_exists(__NAMESPACE__.'\OAuthSignatureMethod_HMAC_SHA1')) {
            $this->loadClass('OAuthSignatureMethod');
            $this->loadClass('OAuthSignatureMethod_HMAC_SHA1');
        }
        $oauth = new OAuthSignatureMethod_HMAC_SHA1();
        $sign = $oauth->build_signature($url, $secret, $token);
        return $sign;
    }
}

