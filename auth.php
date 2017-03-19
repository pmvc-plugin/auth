<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

const SESSION_KEY = 'pmvc_plugin_auth';

\PMVC\l(__DIR__.'/src/BaseProvider.php');
\PMVC\l(__DIR__.'/src/BaseUser.php');

class auth extends \PMVC\PlugIn
{

    public function init()
    {
        $this->initSession();
    }

    public function initSession()
    {
        \PMVC\plug('session')->start();
        if (!isset($_SESSION[SESSION_KEY])) {
            $_SESSION[SESSION_KEY] = new \PMVC\HashMap();
        }
        $this['storage'] = $_SESSION[SESSION_KEY];
    }

    public function getProvider($providerId)
    {
        if (!isset($this[$providerId])) {
            $config = $this->getConfig($providerId);
            $name = \PMVC\get($config, 'name');
            $provider = $this->$name($config);
            if ($name!==$providerId) {
                $this[$providerId] = $provider; 
            }
        } else {
            $provider = $this[$providerId];
        }
        return $provider; 
    }

    public function login($providerId='facebook')
    {
        $provider = $this->getProvider($providerId);
        $provider->loginReturnUrl = $this['return'];
        return $provider->loginBegin();
    }

    public function loginReturn($request,$providerId='facebook')
    {
        $provider = $this->getProvider($providerId);
        $isLogin = $provider->loginFinish($request);
        if ($isLogin) {
            $provider->initUser();
        }
        return $isLogin;
    }

    public function logout()
    {

    }

    public function setIsLogin()
    {
        $this['storage']['isLogin'] = true;
    }

    public function getConfig($providerId)
    {
        $providers = \PMVC\get($this, 'providers');
        return \PMVC\get($providers, $providerId, []);
    }

    public function oauthSign($url, $secret, $token=null)
    {
        if (!$this['oauth']) {
            \PMVC\l(__DIR__.'/src/OAuthSignatureMethod.php');
            \PMVC\l(__DIR__.'/src/OAuthSignatureMethod_HMAC_SHA1.php');
            $this['oauth'] = new OAuthSignatureMethod_HMAC_SHA1();
        }
        $sign = $this['oauth']->build_signature($url, $secret, $token);
        return $sign;
    }
}
