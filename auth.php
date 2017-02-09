<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

const SESSION_KEY = 'pmvc_plugin_auth';

\PMVC\l(__DIR__.'/src/BaseProvider.php');

class auth extends \PMVC\PlugIn
{

    public function init()
    {
        $this->initSession();
    }

    public function initSession()
    {
        \PMVC\initPlugIn(['session'=>null]);
        if (!isset($_SESSION[SESSION_KEY])) {
            $_SESSION[SESSION_KEY] = new \PMVC\HashMap();
        }
        $this['storage'] = $_SESSION[SESSION_KEY];
    }

    public function getProvider($providerName)
    {
        if (!isset($this[$providerName])) {
            $config = $this->getConfig($providerName);
            $provider = $this->$providerName($config);
        } else {
            $provider = $this[$providerName];
        }
        return $this[$providerName]; 
    }

    public function login($providerName='facebook')
    {
        $provider = $this->getProvider($providerName);
        $provider->loginReturnUrl = $this['return'];
        return $provider->loginBegin();
    }

    public function loginReturn($request,$providerName='facebook')
    {
        $provider = $this->getProvider($providerName);
        return $provider->loginFinish($request);
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

    public function loadClass($className)
    {
        if (!class_exists(__NAMESPACE__.'\\'.$className)) {
            \PMVC\l(__DIR__.'/src/'.$className.'.php');
        }
    }

    public function oauthSign($url, $secret, $token=null)
    {
        if (!$this['oauth']) {
            $this->loadClass('OAuthSignatureMethod');
            $this->loadClass('OAuthSignatureMethod_HMAC_SHA1');
            $this['oauth'] = new OAuthSignatureMethod_HMAC_SHA1();
        }
        $sign = $this['oauth']->build_signature($url, $secret, $token);
        return $sign;
    }
}
