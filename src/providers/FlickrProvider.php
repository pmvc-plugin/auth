<?php
namespace PMVC\PlugIn\auth;

/**
 * PMVC Flickr Privider
 */
class FlickrProvider extends ProviderModel
{
    // default permissions, and a lot of them. You can change them from the configuration by setting the scope to what you want/need
    public $scope = "email, user_about_me, user_birthday, user_hometown, user_website";

    /**
    * IDp wrappers initializer
    */
    public function initialize()
    {
        if ( !$this->config['FLICKR_APP_ID'] || !$this->config['FLICKR_APP_SECRET'] ) {
            throw new \Exception("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
        }
        $this->api = \PMVC\plug('flickr',array(
            'appId'=>$this->config['FLICKR_APP_ID'],
            'appSecret'=>$this->config['FLICKR_APP_SECRET']
        ));
    }

    /**
    * begin login step
    *
    * simply call Facebook::require_login().
    */
    public function loginBegin()
    {
        // get the login url
        $url = $this->api->getLoginUrl($this->endpoint);
        $this->token('request_token', $this->api->getToken());
        return $url;
    }


    /**
    * finish login step
    */
    public function loginFinish($request=array())
    {
        // try to get the UID of the connected user from fb, should be > 0
        $token = $this->api->setToken($this->token('request_token'));
        $this->api->getAccessToken($request);
        $token = $this->api->getToken();
        if ( $token ===  $this->token('request_token') ) {
            throw new \Exception("Authentication failed! {$this->providerId} returned an invalid user id.", 5);
        }
        $this->token("access_token", $token);
    }

    /**
    * logout
    */
    public function logout()
    {
        $this->api->destroySession();

        parent::logout();
    }

    /**
    * load the user profile from the IDp api client
    */
    public function getUserProfile()
    {
        $token = $this->api->setToken($this->token('access_token'));
	$method = 'flickr.photos.getNotInSet';
	$args = array(
	    'user_id' => $this->params['access_token']->user_nsid,
            'page'=>3
        );
	$rsp = $this->api->call_method($method, $args);
        return $this->user;
    }

}
