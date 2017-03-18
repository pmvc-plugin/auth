<?php
namespace PMVC\PlugIn\auth;

use PMVC\HashMap;

abstract class BaseProvider extends HashMap
{
    /**
    * Provider id 
    */
    protected $providerId;

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
        if (empty($this->providerId)) {
            return !trigger_error('Need defined provider id');
        }
        $storage = $caller['storage'];
        if (!isset($storage[$this->providerId])) {
            $storage[$this->providerId] = new HashMap(); 
        }
        $this->storage = $storage[$this->providerId];
        if (!isset($this->storage['user'])) {
            $this->storage['user'] = new HashMap();
        }
        $this->user = new BaseUser($this->storage['user']);
        $this->user->setProvider($this->providerId);
    }
    abstract public function loginBegin();
    abstract public function loginFinish(array $request);
    abstract public function initUser();

    public function logout()
    {
        return !trigger_error('Provider not Implement Logout');
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

    public function getProviderId()
    {
        return $this->providerId;
    }
}
