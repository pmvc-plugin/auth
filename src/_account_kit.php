<?php
namespace PMVC\PlugIn\auth;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\AccountKitProvider';

const ACCOUNT_KIT_TOKEN_URL='https://graph.accountkit.com/[VERSION]/access_token';
const ACCOUNT_KIT_ME_URL='https://graph.accountkit.com/[VERSION]/me';

class AccountKitProvider extends BaseProvider
{
    protected $providerId = 'accountKit'; 

    public function &__invoke($configs = null)
    {
        \PMVC\set($this, $configs);
        return $this;
    }

    public function loginBegin()
    {
    }

    public function loginFinish(array $request)
    {
        $access_token = $this->getToken('access_token');
        if (!empty($access_token)) {
            return $access_token;
        }
        $app = \PMVC\get($this,'app');
        $version = \PMVC\get($app, 'version');
        $id = \PMVC\get($app, 'id');
        $secret = \PMVC\get($app, 'secret');
        $token = join('|',[
            'AA',
            $id,
            $secret
        ]);
        $tokenUrl = str_replace('[VERSION]', $version, ACCOUNT_KIT_TOKEN_URL);
        $oUrl = \PMVC\plug('url')->getUrl($tokenUrl);
        \PMVC\set($oUrl->query, [
            'grant_type'=>'authorization_code',
            'access_token'=>$token,
            'code'=>\PMVC\get($request, 'code')
        ]);
        $curl = \PMVC\plug('curl');
        $curl->get($oUrl, function($r) {
            $body = \PMVC\fromJson($r->body); 
            if (isset($body->error)) {
                trigger_error(\PMVC\value($body,[
                    'error',
                    'message'
                ]));
            }
            $token = \PMVC\get($body,'access_token');
            if (!empty($token)) {
                $this->setToken(
                    'access_token', 
                    $token
                );
            }
        });
        $curl->process();
        return $this->getToken('access_token');
    }

    public function initUser()
    {
        $version = \PMVC\value($this, ['app','version']);
        $secret = \PMVC\value($this, ['app', 'secret']);
        $meUrl = str_replace('[VERSION]', $version, ACCOUNT_KIT_ME_URL);
        $access_token = $this->getToken('access_token');
        $appsecret_proof = hash_hmac('sha256', $access_token, $secret);
        $curl = \PMVC\plug('curl');
        $curl->get($meUrl, function($r) {
            $json = \PMVC\fromJson($r->body);
            $this->user->setId(\PMVC\get($json, 'id'));
            $this->user->setEmail(\PMVC\value($json, ['email','address']));
        }, [
            'access_token'=>$access_token,
            'appsecret_proof'=>$appsecret_proof
        ]);
        $curl->process();
    }
}
