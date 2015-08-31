<?php
namespace PMVC\PlugIn\auth;

class User
{

    public $profile;

    public $identifier;

    public function __construct()
    {
        $this->profile = new \PMVC\HashMap();
    }
}
