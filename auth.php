<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

\PMVC\l(__DIR__.'/src/BaseProvider.php');
\PMVC\l(__DIR__.'/src/BaseUser.php');

if (!class_exists(${_INIT_CONFIG}[_CLASS])) {
class auth extends \PMVC\PlugIn
{
    const SESSION_KEY = 'pmvc_plugin_auth';
    public function init()
    {
        $this->initSession();
        $this['bcookie'] = 'b';
    }

    public function initSession()
    {
        \PMVC\plug('session')->start();
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = new \PMVC\HashMap();
        }
        $this['store'] = $_SESSION[self::SESSION_KEY];
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
        $isAuthorized = $provider->loginFinish($request);
        if ($isAuthorized) {
            $provider->initUser();
            $this->setIsLogin();
        }
        return $isAuthorized;
    }

    public function logout()
    {
        $store = $this['store'];
        $key = $store['authKey'];
        $session = \PMVC\plug('session');
        $session->setCookie($key, null);
        unset($store['authKey']);
        unset($store['authHash']);
    }

    public function isLogin()
    {
        $store = $this['store'];
        $key = $store['authKey'];
        $hash = $store['authHash'];
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
        return true;
    }

    public function setIsLogin()
    {
        $store = $this['store'];
        $store['isAuthorized'] = true;
        $guid = \PMVC\plug('guid');
        $key = $guid->gen();
        $value = $guid->gen();
        $store['authKey'] = $key; 
        $store['authHash'] = $this->hashIsAuth(
            $value,
            \PMVC\get($_COOKIE, $this['bcookie']) 
        );
        $session = \PMVC\plug('session');
        $session->setCookie($key, $value);
        return $value;
    }

    public function hashIsAuth($authValue, $bcookie)
    {
        return crypt(
            $authValue,
            $bcookie
        );
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
}
