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
        $this['bookie'] = 'b';
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

    public function login($providerId)
    {
        $provider = $this->getProvider($providerId);
        $provider->loginReturnUrl = $this['return'];
        return $provider->loginBegin();
    }

    public function loginReturn($request, $providerId)
    {
        $provider = $this->getProvider($providerId);
        $isLogin = $provider->loginFinish($request);
        if ($isLogin) {
            $provider->initUser();
            $this->setIsAuthorized();
        }
        return $isLogin;
    }

    public function logout()
    {

    }

    public function isLogin()
    {
        $storage = $this['storage'];
        $key = $storage['authKey'];
        $hash = $storage['authHash'];
        if (!$key || !$hash) {
            return false;
        }
        $value = \PMVC\get($_COOKIE, $key);
        $bcookie = \PMVC\get($_COOKIE, $this['bcookie']);
        if (!$value || !$bcookie) {
            return false;
        }
        $verify = $this->hashIsAuth($value, $bcookie);
        if ($verify !== $hash) {
            return false;
        }
    }

    public function setIsAuthorized()
    {
        $storage = $this['storage'];
        $storage['isAuthorized'] = true;
        $guid = \PMVC\plug('guid');
        $key = $guid->gen();
        $value = $guid->gen();
        $storage['authKey'] = $key; 
        $storage['authHash'] = $this->hashIsAuth(
            $value,
            \PMVC\get($_COOKIE, $this['bcookie']) 
        );
        $session = \PMVC\plug('session');
        $session->setCookie($key, $value);
    }

    public function hashIsAuth($authValue, $bcookie)
    {
        return crypt(
            $authValue,
            $bcookie
        );
    }

    public function setIsRegisted()
    {
        $this['storage']['isRegisted'] = true;
    }


    public function getDefaultProvider()
    {
        return $this['defaultProvider'];
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
