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

    const GRAPH_URL = 'https://graph.facebook.com/v2.8';
    protected $providerId = 'facebook'; 

    /**
     * Default Permission
     * https://developers.facebook.com/docs/facebook-login/permissions/review
     */
    public $permission = [
        'email',
        'public_profile',
        'user_friends'
    ];

    public function &__invoke($configs = null)
    {
        \PMVC\set($this, $configs);
        $this->api = new Facebook(array( 
            'app_id' => \PMVC\get($this['app'], 'id'), 
            'app_secret' => \PMVC\get($this['app'], 'secret'), 
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
        $this->store['state'] = $fbStore->get('state');
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
        $token = $this->handleToken();
        if ( !$token ) {
            return !trigger_error('Authentication failed!');
        }
        return true;
    }

    public function handleToken()
    {
        $helper = $this->api->getRedirectLoginHelper();
        $store = $helper->getPersistentDataHandler();
        $store->set(
            'state',
            \PMVC\get($this->store, 'state')
        );
        $accessToken = $helper->getAccessToken();
        // store facebook access token
        $setResult = $this->setToken('access_token', $accessToken);
        if (!$setResult) {
            trigger_error('Set facebook token to session fail');
        }
        return $accessToken;
    }

    public function initUser()
    {
        $fields = [ 
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
        ];
        $url = \PMVC\plug('url')->getUrl(self::GRAPH_URL.'/me');
        $url->query->fields = join(',', $fields);
        $url->query->access_token = (string)$this->getToken('access_token');
        $curl = \PMVC\plug('curl');
        $curl->get($url, function ($r) {
            $json = \PMVC\fromJson($r->body);
            \PMVC\set($this->user, $json);
            $this->user->setId(\PMVC\get($json, 'id'));
            $this->user->setEmail(\PMVC\get($json, 'email'));
            $this->user->setLegalName(
                \PMVC\get($json, 'first_name').
                ' '.
                \PMVC\get($json, 'last_name')
            );
        });
        $curl->process();
    }
}
