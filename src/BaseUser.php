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

    public function setProvider($providerId)
    {
        $this->_provider = $providerId;
        $this->_db = ucfirst($providerId).'Users';
    }

    public function getProvider()
    {
        return $this->_provider;
    }

    public function getDb()
    {
        return $this->_db;
    }

    public function getId()
    {
        return $this['id'];
    }

    public function setId($id)
    {
        $this['id'] = $id;
        return $this['id'];
    }

    public function getEmail()
    {
        return $this['email'];
    }

    public function setEmail($email)
    {
        $this['email'] = $email;
        return $this['email'];
    }

    public function getLegalName()
    {
        return $this['legalName'];
    }

    public function setLegalName($legalName)
    {
        $this['legalName'] = $legalName;
        return $this['legalName'];
    }
}
