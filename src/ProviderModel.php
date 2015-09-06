<?php
namespace PMVC\PlugIn\auth;

/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Provider_Model provide a common interface for supported IDps on HybridAuth.
 *
 * Basically, each provider adapter has to define at least 4 methods:
 *   Hybrid_Providers_{provider_name}::initialize()
 *   Hybrid_Providers_{provider_name}::loginBegin()
 *   Hybrid_Providers_{provider_name}::loginFinish()
 *   Hybrid_Providers_{provider_name}::getUserProfile()
 *
 * HybridAuth also come with three others models
 *   Class Hybrid_Provider_Model_OpenID for providers that uses the OpenID 1 and 2 protocol.
 *   Class Hybrid_Provider_Model_OAuth1 for providers that uses the OAuth 1 protocol.
 *   Class Hybrid_Provider_Model_OAuth2 for providers that uses the OAuth 2 protocol.
 */
abstract class ProviderModel
{
    /**
     * IDp ID (or unique name)
     * @var Numeric/String
     */
    public $providerId = null;

    /**
     * specific provider adapter config
     * @var array
     */
    public $config     = null;

    /**
     * provider extra parameters
     * @var array
     */
    public $params     = null;

    /**
     * Endpoint URL for that provider
     * @var String
     */
    public $endpoint   = null;

    /**
     * Hybrid_User obj, represents the current loggedin user
     * @var object
     */
    public $user       = null;

    /**
     * the provider api client (optional)
     * @var String
     */
    public $api        = null;

    /**
     * Common providers adapter constructor
     * @param Numeric/String $providerId
     * @param Array $config
     * @param Array $params
     */
    public function __construct($providerId, $config, &$params = null)
    {
        $this->params =& $params;

        // idp id
        $this->providerId = $providerId;

        // idp config
        $this->config = $config;

        // new user instance
        $this->user = new \PMVC\HashMap();
        $this->user->profile = new \PMVC\HashMap();
        $this->user->providerId = $providerId;
        $this->user->timestamp = time();

        // initialize the current provider adapter
        $this->initialize();

        Logger::debug("Hybrid_Provider_Model::__construct( $providerId ) initialized. dump current adapter instance: ", serialize($this));
    }

    // --------------------------------------------------------------------

    /**
    * IDp wrappers initializer
    *
    * The main job of wrappers initializer is to performs (depend on the IDp api client it self):
    *     - include some libs needed by this provider,
    *     - check IDp key and secret,
    *     - set some needed parameters (stored in $this->params) by this IDp api client
    *     - create and setup an instance of the IDp api client on $this->api
    */
    abstract protected function initialize();

    // --------------------------------------------------------------------

    /**
    * begin login
    */
    abstract protected function loginBegin();

    // --------------------------------------------------------------------

    /**
    * finish login
    */
    abstract protected function loginFinish();

    // --------------------------------------------------------------------

    /**
    * generic logout, just erase current provider adapter stored data to let Hybrid_Auth all forget about it
    */
    public function logout()
    {
        Logger::info("Enter [{$this->providerId}]::logout()");

        $this->clearTokens();

        return true;
    }

    // --------------------------------------------------------------------

    /**
    * grab the user profile from the IDp api client
    */
    public function getUserProfile()
    {
        Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");

        throw new Exception("Provider does not support this feature.", 8);
    }

    // --------------------------------------------------------------------

    /**
    * load the current logged in user contacts list from the IDp api client
    */
    public function getUserContacts()
    {
        Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");

        throw new Exception("Provider does not support this feature.", 8);
    }

    // --------------------------------------------------------------------

    /**
    * return the user activity stream
    */
    public function getUserActivity($stream)
    {
        Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");

        throw new Exception("Provider does not support this feature.", 8);
    }

    // --------------------------------------------------------------------

    /**
    * set user status
    */
    public function setUserStatus($status)
    {
        Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");

        throw new Exception("Provider does not support this feature.", 8);
    }


    /**
    * return the user status
    */
    public function getUserStatus($statusid)
    {
        Logger::error("HybridAuth do not provide user's status for {$this->providerId} yet.");

        throw new Exception("Provider does not support this feature.", 8);
    }

    // --------------------------------------------------------------------

    /**
    * return true if the user is connected to the current provider
    */
    public function isUserConnected()
    {
        return null;
    }

    // --------------------------------------------------------------------

    /**
    * set user to connected
    */
    public function setUserConnected()
    {
        Logger::info("Enter [{$this->providerId}]::setUserConnected()");
    }

    // --------------------------------------------------------------------

    /**
    * set user to unconnected
    */
    public function setUserUnconnected()
    {
        Logger::info("Enter [{$this->providerId}]::setUserUnconnected()");
    }

    // --------------------------------------------------------------------

    /**
    * get or set a token
    */
    public function token($token, $value = null)
    {
        if (!is_null($value)) {
            $this->params[$token] = $value;
        }
        if (isset($this->params[$token])) {
            return $this->params[$token];
        } else {
            return null;
        }
    }

    // --------------------------------------------------------------------

    /**
    * delete a stored token
    */
    public function deleteToken($token)
    {
    }

    // --------------------------------------------------------------------

    /**
    * clear all existent tokens for this provider
    */
    public function clearTokens()
    {
    }
}
