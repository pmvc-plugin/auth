<?php
namespace PMVC\PlugIn\auth;

use Facebook\Facebook;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\FacebookProvider';

/**
 * API
 *   https://github.com/facebook/php-graph-sdk
 *   https://packagist.org/packages/facebook/graph-sdk
 */
class FacebookProvider extends BaseProvider 
{

    protected $providerName = 'facebook'; 

    /**
     * Default Permission
     */
    public $permission = [
        'email',
        'user_about_me',
        'user_birthday',
        'user_hometown',
        'user_website'
    ];

    public function __invoke($configs)
    {
        \PMVC\set($this, $configs);
        $this->api = new Facebook(array( 
            'app_id' => $this['FACEBOOK_APP_ID'], 
            'app_secret' => $this['FACEBOOK_APP_SECRET'], 
            'persistent_data_handler' => 'memory'
        ));
        return $this;
    }

    /**
     * Before request FB server
     */
    public function loginBegin()
    {
        $helper = $this->api->getRedirectLoginHelper();
        $url = $helper->getLoginUrl(
            $this->loginReturnUrl,
            $this->permission
        );
        $fbStore = $helper->getPersistentDataHandler();
        $this->storage['state'] = $fbStore->get('state');
        $url .= '&display=popup';
        return $url;
    }

    /**
     * After request FB server
     */
    public function loginFinish(array $request)
    {
        // User not accept permission 
        if (isset($request['error']) && $request['error'] === 'access_denied') {
            trigger_error('Authentication failed! The user denied your request.');
        }

        // get token fail 
        $token = $this->isLogin();
        if ( !$token ) {
            return !trigger_error('Authentication failed!');
        }
        return true;
    }

    public function isLogin()
    {
        $helper = $this->api->getRedirectLoginHelper();
        $store = $helper->getPersistentDataHandler();
        $store->set(
            'state',
            \PMVC\get($this->storage, 'state')
        );
        $accessToken = $helper->getAccessToken();
        // store facebook access token
        $setResult = $this->setToken('access_token', $accessToken);
        if (!$setResult) {
            trigger_error('Set facebook token to session fail');
        }
        return $accessToken;
    }

    public function getUserProfile()
    {

    }
}
