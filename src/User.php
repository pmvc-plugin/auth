<?php
namespace PMVC\PlugIn\auth;

class User
{

    public $profile;

    public $providerId;

    public $timestamp = null;

    public function __construct()
    {
        $this->profile = new \PMVC\HashMap();
        $this->timestamp = time();
    }
}
