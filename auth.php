<?php

namespace PMVC\PlugIn\auth;

use DomainException;
use PMVC\HashMap;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\auth';

\PMVC\l(__DIR__.'/src/BaseProvider.php');
\PMVC\l(__DIR__.'/src/BaseUser.php');

if (!class_exists(${_INIT_CONFIG}[_CLASS])) {
class auth extends \PMVC\PlugIn
{
    const SESSION_KEY = 'pmvc_plugin_auth';
    const REGISTERED_KEY = 'registered';

    public function init()
    {
        \PMVC\arrayReplace(
            $this,
            \PMVC\get($this),
            $this->defaultValue()
        );
        $this->initSession();
    }

    public function defaultValue()
    {
        return [
            'bcookie'=>'b',
            'lifetime'=>86400*7,
        ];
    }

    public function initSession()
    {
        \PMVC\plug('session')->start();
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = new HashMap();
        }
        $this['store'] = $_SESSION[self::SESSION_KEY];
    }

    public function getProvider($providerId)
    {
        if (!isset($this[$providerId])) {
            $config = $this->getConfig($providerId);
            $name = \PMVC\get($config, 'name');
            if (empty($name) || !$this->isCallable($name)) {
                throw new DomainException('Provider not exits. ['.$name.']');
            }
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
            $this->setIsAuth();
        }
        return $isAuthorized;
    }

    public function logout()
    {
        $store = $this['store'];
        $key = $store['authKey'];
        $session = \PMVC\plug('session');
        $session->setCookie($key, null);
        $_SESSION[self::SESSION_KEY] = new HashMap();
    }

    public function isAuth()
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
            $this->logout();
            return false;
        }
        $verify = $this->hashIsAuth($value, $bcookie);
        if ($verify !== $hash) {
            $this->logout();
            return false;
        }
        return true;
    }

    public function isExpire()
    {
        if (!$this->isAuth()) {
            return true;
        }
        $store = $this['store'];
        $key = $store['authKey'];
        if (!empty($key)) {
            $value = \PMVC\get($_COOKIE, $key);
            $time = \PMVC\plug('guid')->verify($value);
        } else {
            $time = -1;
        }
        if ($time < date('YmdHis', time() - $this['lifetime'])) {
            return true;
        } else {
            return false;
        }
    }

    public function setIsAuth()
    {
        $store = $this['store'];
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
        if (empty($bcookie)) {
            return !trigger_error('Can\'t get browser cookie');
        }
        return crypt(
            $authValue,
            $bcookie
        );
    }

    public function setRegistered($registerId)
    {
        if (!$this->isAuth() || empty($registerId)) {
            return false;
        }
        $store = $this['store'];
        $store[self::REGISTERED_KEY] = $registerId;
        return $store[self::REGISTERED_KEY];
    }

    public function getRegistered()
    {
        if (!$this->isAuth()) {
            return false;
        }
        $store = $this['store'];
        return $store[self::REGISTERED_KEY];
    }

    public function getCurrentProvider()
    {
        return \PMVC\value($this, ['store', 'currentProvider']);
    }

    public function setCurrentProvider($providerId)
    {
        $store = $this['store'];
        $store['currentProvider'] = $providerId;
        return $store['currentProvider'];
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
