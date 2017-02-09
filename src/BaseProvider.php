<?php
namespace PMVC\PlugIn\auth;

use PMVC\HashMap;

abstract class BaseProvider extends HashMap
{
    /**
    * Provider Name 
    */
    protected $providerName;

    /**
    * Login Return Url
    */
    public $loginReturnUrl;

    /**
    * User information
    */
    public $user;

    /**
    * Persistent Storage
    */
    public $storage;

    /**
    * Provider API
    */
    public $api;

    public function __construct($caller)
    {
        $this->user = new HashMap();
        $storage = $caller['storage'];
        if (!isset($storage[$this->providerName])) {
            $storage[$this->providerName] = new HashMap(); 
        }
        $this->storage = $storage[$this->providerName];
    }
    abstract public function loginBegin();
    abstract public function loginFinish(array $request);

    public function logout()
    {
        return !trigger_error('Provider not Implement Logout');
    }

    public function isLogin()
    {
        return !trigger_error('Provider not Implement isLogin');
    }

    public function getUserProfile()
    {
        return !trigger_error('Provider not Implement getUserProfile');
    }

    public function setToken($tokenKey, $value)
    {
        $this->storage[$tokenKey] = $value;
        return $this->storage[$tokenKey];
    }

    public function getToken($tokenKey)
    {
        return $this->storage[$tokenKey];
    }

    public function getProviderName()
    {
        return $this->providerName;
    }
}
