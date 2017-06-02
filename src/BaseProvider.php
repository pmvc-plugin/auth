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
    public $store;

    /**
    * Provider API
    */
    public $api;

    public function __construct($caller)
    {
        if (empty($this->providerId)) {
            return !trigger_error('Need defined provider id');
        }
        $store = $caller['store'];
        if (!isset($store[$this->providerId])) {
            $store[$this->providerId] = new HashMap(); 
        }
        $this->store = $store[$this->providerId];
        if (!isset($this->store['user'])) {
            $this->store['user'] = new HashMap();
        }
        $this->user = new BaseUser($this->store['user']);
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
        $this->store[$tokenKey] = $value;
        return $this->store[$tokenKey];
    }

    public function getToken($tokenKey)
    {
        return $this->store[$tokenKey];
    }

    public function getProviderId()
    {
        return $this->providerId;
    }
}
