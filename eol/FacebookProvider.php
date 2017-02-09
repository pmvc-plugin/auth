<?php

namespace PMVC\PlugIn\auth;

use Facebook\Facebook;

/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Facebook provider adapter based on OAuth2 protocol
 *
 * Hybrid_Providers_Facebook use the Facebook PHP SDK created by Facebook
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
 */
class FacebookProvider extends ProviderModel
{
    // default permissions, and a lot of them. You can change them from the configuration by setting the scope to what you want/need
    public $scope = "email, user_about_me, user_birthday, user_hometown, user_website";

    /**
    * IDp wrappers initializer
    */
    public function initialize()
    {
        if (! $this->config['FACEBOOK_APP_ID'] || ! $this->config['FACEBOOK_APP_SECRET']) {
            throw new \Exception("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
        }
        $auth = \PMVC\plug('auth');

        if (isset($auth["proxy"])) {
            BaseFacebook::$CURL_OPTS[CURLOPT_PROXY] = $auth["proxy"];
        }
        $this->api = new Facebook(array( 
            'app_id' => $this->config['FACEBOOK_APP_ID'], 
            'app_secret' => $this->config['FACEBOOK_APP_SECRET'], 
            'persistent_data_handler' => 'memory'
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
        $helper = $this->api->getRedirectLoginHelper();
        $url = $helper->getLoginUrl($this->endpoint, explode(',',$this->scope));
        $store = $helper->getPersistentDataHandler();
        $this->params['state'] = $store->get('state');
        $url .= '&display=popup';
        return $url;
    }

    public function isLogin()
    {
        $helper = $this->api->getRedirectLoginHelper();
        $store = $helper->getPersistentDataHandler();
        $store->set('state',$this->params['state']);
        $accessToken = $helper->getAccessToken();
        // store facebook access token
        $this->token("access_token", $accessToken);
        return $accessToken;
    }

    /**
    * finish login step
    */
    public function loginFinish($request=array())
    {
        // in case we get error_reason=user_denied&error=access_denied
        if (isset($request['error']) && $request['error'] == "access_denied") {
            throw new \Exception("Authentication failed! The user denied your request.", 5);
        }

        // try to get the UID of the connected user from fb, should be > 0
        $token = $this->isLogin();
        if ( !$token ) {
            throw new \Exception("Authentication failed! {$this->providerId} returned an invalid user id.", 5);
        }

        // set user as logged in
        $this->setUserConnected();

    }

    /**
    * logout
    */
    public function logout()
    {
        $this->api->destroySession();
    }

    /**
    * load the user profile from the IDp api client
    */
    public function getUserProfile()
    {
        // request user profile from fb api
        $fields = array(
            'email',
            'name',
            'first_name',
            'last_name',
            'link',
            'website',
            'gender',
            'locale',
            'about',
            'hometown',
            'birthday'
        );
        $this->api->setDefaultAccessToken($this->token('access_token'));
        try {
            $url = '/me?fields='.join(',',$fields);
            $response = $this->api->get($url);
            $data = $response->getDecodedBody();
        } catch (FacebookApiException $e) {
            throw new \Exception("User profile request failed! {$this->providerId} returned an error: $e", 6);
        }

        // if the provider identifier is not received, we assume the auth has failed
        if (! isset($data["id"])) {
            throw new \Exception("User profile request failed! {$this->providerId} api returned an invalid response.", 6);
        }

        # store the user profile.
        $this->user->profile->identifier    = (array_key_exists('id', $data))?$data['id']:"";
        $this->user->profile->displayName   = (array_key_exists('name', $data))?$data['name']:"";
        $this->user->profile->firstName     = (array_key_exists('first_name', $data))?$data['first_name']:"";
        $this->user->profile->lastName      = (array_key_exists('last_name', $data))?$data['last_name']:"";
        $this->user->profile->photoURL      = "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?width=150&height=150";
        $this->user->profile->coverInfoURL  = "https://graph.facebook.com/" . $this->user->profile->identifier . "?fields=cover&access_token=".$this->token('access_token');
        $this->user->profile->profileURL    = (array_key_exists('link', $data))?$data['link']:"";
        $this->user->profile->webSiteURL    = (array_key_exists('website', $data))?$data['website']:"";
        $this->user->profile->gender        = (array_key_exists('gender', $data))?$data['gender']:"";
        $this->user->profile->language      = (array_key_exists('locale', $data))?$data['locale']:"";
        $this->user->profile->description   = (array_key_exists('about', $data))?$data['about']:"";
        $this->user->profile->email         = (array_key_exists('email', $data))?$data['email']:"";
        $this->user->profile->emailVerified = (array_key_exists('email', $data))?$data['email']:"";
        $this->user->profile->region        = (array_key_exists("hometown", $data)&&array_key_exists("name", $data['hometown']))?$data['hometown']["name"]:"";
        
        if (!empty($this->user->profile->region)) {
            $regionArr = explode(',', $this->user->profile->region);
            if (count($regionArr) > 1) {
                $this->user->profile->city = trim($regionArr[0]);
                $this->user->profile->country = trim($regionArr[1]);
            }
        }
        
        if (array_key_exists('birthday', $data)) {
            list($birthday_month, $birthday_day, $birthday_year) = explode("/", $data['birthday']);

            $this->user->profile->birthDay   = (int) $birthday_day;
            $this->user->profile->birthMonth = (int) $birthday_month;
            $this->user->profile->birthYear  = (int) $birthday_year;
        }

        return $this->user;
    }

    /**
    * Attempt to retrieve the url to the cover image given the coverInfoURL
    *
    * @param  string $coverInfoURL   coverInfoURL variable
    * @retval string                 url to the cover image OR blank string
    */
    public function getCoverURL($coverInfoURL)
    {
        try {
            $headers = get_headers($coverInfoURL);
            if (substr($headers[0], 9, 3) != "404") {
                $coverOBJ = json_decode(file_get_contents($coverInfoURL));
                if (array_key_exists('cover', $coverOBJ)) {
                    return $coverOBJ->cover->source;
                }
            }
        } catch (\Exception $e) {
        }

        return "";
    }
    
    /**
    * load the user contacts
    */
    public function getUserContacts()
    {
        $apiCall = '?fields=link,name';
        $returnedContacts = array();
        $pagedList = false;

        do {
            try {
                $response = $this->api->api('/me/friends' . $apiCall);
            } catch (FacebookApiException $e) {
                throw new \Exception('User contacts request failed! {$this->providerId} returned an error: $e');
            }

            // Prepare the next call if paging links have been returned
            if (array_key_exists('paging', $response) && array_key_exists('next', $response['paging'])) {
                $pagedList = true;
                $next_page = explode('friends', $response['paging']['next']);
                $apiCall = $next_page[1];
            } else {
                $pagedList = false;
            }

            // Add the new page contacts
            $returnedContacts = array_merge($returnedContacts, $response['data']);
        } while ($pagedList == true);

        $contacts = array();
 
        foreach ($returnedContacts as $item) {
            $uc = new Hybrid_User_Contact();

            $uc->identifier  = (array_key_exists("id", $item))?$item["id"]:"";
            $uc->displayName = (array_key_exists("name", $item))?$item["name"]:"";
            $uc->profileURL  = (array_key_exists("link", $item))?$item["link"]:"https://www.facebook.com/profile.php?id=" . $uc->identifier;
            $uc->photoURL    = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";

            $contacts[] = $uc;
        }

        return $contacts;
    }

    /**
    * update user status
    *
    * @param  string $pageid   (optional) User page id
    */
    public function setUserStatus($status, $pageid = null)
    {
        if (!is_array($status)) {
            $status = array( 'message' => $status );
        }

        if (is_null($pageid)) {
            $pageid = 'me';

        // if post on page, get access_token page
        } else {
            $access_token = null;
            foreach ($this->getUserPages(true) as $p) {
                if (isset($p[ 'id' ]) && intval($p['id']) == intval($pageid)) {
                    $access_token = $p[ 'access_token' ];
                    break;
                }
            }

            if (is_null($access_token)) {
                throw new \Exception("Update user page failed, page not found or not writable!");
            }

            $status[ 'access_token' ] = $access_token;
        }

        try {
            $response = $this->api->api('/' . $pageid . '/feed', 'post', $status);
        } catch (FacebookApiException $e) {
            throw new \Exception("Update user status failed! {$this->providerId} returned an error: $e");
        }

        return $response;
    }


    /**
    * get user status
    */
    public function getUserStatus($postid)
    {
        try {
            $postinfo = $this->api->api("/" . $postid);
        } catch (FacebookApiException $e) {
            throw new \Exception("Cannot retrieve user status! {$this->providerId} returned an error: $e");
        }

        return $postinfo;
    }


    /**
    * get user pages
    */
    public function getUserPages($writableonly = false)
    {
        if ((isset($this->config[ 'scope' ]) && strpos($this->config[ 'scope' ], 'manage_pages') === false) || (!isset($this->config[ 'scope' ]) && strpos($this->scope, 'manage_pages') === false)) {
            throw new \Exception("User status requires manage_page permission!");
        }

        try {
            $pages = $this->api->api("/me/accounts", 'get');
        } catch (FacebookApiException $e) {
            throw new \Exception("Cannot retrieve user pages! {$this->providerId} returned an error: $e");
        }

        if (!isset($pages[ 'data' ])) {
            return array();
        }

        if (!$writableonly) {
            return $pages[ 'data' ];
        }

        $wrpages = array();
        foreach ($pages[ 'data' ] as $p) {
            if (isset($p[ 'perms' ]) && in_array('CREATE_CONTENT', $p[ 'perms' ])) {
                $wrpages[] = $p;
            }
        }

        return $wrpages;
    }

    /**
    * load the user latest activity
    *    - timeline : all the stream
    *    - me       : the user activity only
    */
    public function getUserActivity($stream)
    {
        try {
            if ($stream == "me") {
                $response = $this->api->api('/me/feed');
            } else {
                $response = $this->api->api('/me/home');
            }
        } catch (FacebookApiException $e) {
            throw new \Exception("User activity stream request failed! {$this->providerId} returned an error: $e");
        }

        if (! $response || ! count($response['data'])) {
            return array();
        }

        $activities = array();

        foreach ($response['data'] as $item) {
            if ($stream == "me" && $item["from"]["id"] != $this->api->getUser()) {
                continue;
            }

            $ua = new Hybrid_User_Activity();

            $ua->id                 = (array_key_exists("id", $item))?$item["id"]:"";
            $ua->date               = (array_key_exists("created_time", $item))?strtotime($item["created_time"]):"";

            if ($item["type"] == "video") {
                $ua->text           = (array_key_exists("link", $item))?$item["link"]:"";
            }

            if ($item["type"] == "link") {
                $ua->text           = (array_key_exists("link", $item))?$item["link"]:"";
            }

            if (empty($ua->text) && isset($item["story"])) {
                $ua->text           = (array_key_exists("link", $item))?$item["link"]:"";
            }

            if (empty($ua->text) && isset($item["message"])) {
                $ua->text           = (array_key_exists("message", $item))?$item["message"]:"";
            }

            if (! empty($ua->text)) {
                $ua->user->identifier   = (array_key_exists("id", $item["from"]))?$item["from"]["id"]:"";
                $ua->user->displayName  = (array_key_exists("name", $item["from"]))?$item["from"]["name"]:"";
                $ua->user->profileURL   = "https://www.facebook.com/profile.php?id=" . $ua->user->identifier;
                $ua->user->photoURL     = "https://graph.facebook.com/" . $ua->user->identifier . "/picture?type=square";

                $activities[] = $ua;
            }
        }

        return $activities;
    }
}
