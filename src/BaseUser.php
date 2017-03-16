<?php

namespace PMVC\PlugIn\auth;

use PMVC\HashMap;

class BaseUser extends HashMap
{
    private $_provider;
    private $_db;

    public function __construct($state)
    {
        $this->state = $state;
    }

    public function setProvider($providerName)
    {
        $this->_provider = $providerName;
        $this->_db = ucfirst($providerName).'Users';
    }

    public function getDb()
    {
        return $this->_db;
    }

    public function getId()
    {
        return $this['id'];
    }

    public function getEmail()
    {
        return $this['email'];
    }
}
