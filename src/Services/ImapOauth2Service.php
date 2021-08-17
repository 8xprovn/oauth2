<?php

namespace ImapOauth2\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use ImapOauth2\Auth\Guard\ImapOauth2WebGuard;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Session;

class ImapOauth2Service
{
    /**
     * The Session key for token
     */
    const ImapOauth2_SESSION = 'imap_authen_user_';

    /**
     * ImapOauth2 URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * ImapOauth2 Realm
     *
     * @var string
     */
    protected $realm;

    /**
     * ImapOauth2 Client ID
     *
     * @var string
     */
    protected $clientId;

    /**
     * ImapOauth2 Client Secret
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * ImapOauth2 OpenId Configuration
     *
     * @var array
     */
    protected $openid;

    /**
     * ImapOauth2 OpenId Cache Configuration
     *
     * @var array
     */
    protected $cacheOpenid;

    /**
     * CallbackUrl
     *
     * @var array
     */
    protected $callbackUrl;


    protected $googleCallbackUrl;

    protected $facebookCallbackUrl;

    /**
     * RedirectLogout
     *
     * @var array
     */
    protected $redirectLogout;

    protected $userProfile;
    /**
     * The Constructor
     * You can extend this service setting protected variables before call
     * parent constructor to comunicate with ImapOauth2 smoothly.
     *
     * @param ClientInterface $client
     * @return void
     */
    public function __construct(ClientInterface $client)
    {
        if (is_null($this->baseUrl)) {
            $this->baseUrl = config('imapoauth.base_oauth_url');
        }

        if (is_null($this->clientId)) {
            $this->clientId = config('imapoauth.client_id');
        }

        if (is_null($this->clientSecret)) {
            $this->clientSecret = config('imapoauth.client_secret');
        }

        if (is_null($this->callbackUrl)) {
            $this->callbackUrl = route('ImapOauth2.callback');
        }

        if (is_null($this->googleCallbackUrl)) {
            $this->googleCallbackUrl = route('ImapOauth2.google_callback');
        }

        if (is_null($this->facebookCallbackUrl)) {
            $this->facebookCallbackUrl = route('ImapOauth2.facebook_callback');
        }

        if (is_null($this->redirectLogout)) {
            $this->redirectLogout =  route('ImapOauth2.redirect_logout');
        }

       

        $this->httpClient = $client;
    }

    /**
     * Return the login URL
     *
     * @link https://openid.net/specs/openid-connect-core-1_0.html#CodeFlowAuth
     *
     * @return string
     */
    public function getLoginUrl()
    {

        $url = $this->baseUrl.'/oauth/authenticate/';
        $params = [
            'client_id' => $this->clientId,
            'grant_type'=> 'authorization_code',
            'response_type' => 'code',
            'redirect_uri' => $this->callbackUrl,
            'state' => 'mystate'
        ];

        return $this->buildUrl($url, $params);
    }

    /**
     * Return the logout URL
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        $url = $this->baseUrl.'/oauth/logout';

        return $this->buildUrl($url, ['redirect_uri' => $this->redirectLogout]);
    }

    /**
     * Return the register URL
     *
     * @link https://stackoverflow.com/questions/51514437/ImapOauth2-direct-user-link-registration
     *
     * @return string
     */
    public function getRegisterUrl()
    {
        return $this->getOauthUrl($this->callbackUrl);

    }

    public function getOauthGoogleUrl()
    {
        return $this->getOauthUrl($this->googleCallbackUrl, 'oauth/authenticate', 'google');

    }

    public function getOauthFacebookUrl()
    {
        return $this->getOauthUrl($this->facebookCallbackUrl, 'oauth/authenticate', 'facebook');

    }

    public function getGoogleUrlCallback(){
        return $this->googleCallbackUrl;
    }

    public function getFacebookUrlCallback(){
        return $this->facebookCallbackUrl;
    }

    
    /**
     * Get access token from Code
     *
     * @param  string $code
     * @return array
     */
    public function getAccessToken($code, $callbackUrl = null)
    {
        if(!$callbackUrl) {
            $callbackUrl = $this->callbackUrl;
        }

        // /dd($callbackUrl);

        $url =  $this->baseUrl.'/oauth/token';
        $params = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secrect' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callbackUrl,
        ];

        if (! empty($this->clientSecret)) {
            $params['client_secret'] = $this->clientSecret;
        }

        $token = [];

        try {

            $response = $this->httpClient->request('POST', $url, ['form_params' => $params]);

            if ($response->getStatusCode() === 200) {
                $token = $response->getBody()->getContents();
                $token = json_decode($token, true);
            }

        } catch (GuzzleException $e) {
       
            $this->logException($e);
        }

        return $token;
    }

    /**
     * Refresh access token
     *
     * @param  string $refreshToken
     * @return array
     */
    public function refreshAccessToken($credentials, $callbackUrl = null)
    {
        if(!$callbackUrl) {
            $callbackUrl = $this->callbackUrl;
        }
        if (empty($credentials['refresh_token'])) {
            return [];
        }

        $url =  $this->baseUrl.'/oauth/token';
        $params = [
            'client_id' => $this->clientId,
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
            'redirect_uri' => $callbackUrl,
        ];

        if (! empty($this->clientSecret)) {
            $params['client_secret'] = $this->clientSecret;
        }

        $token = [];

        try {
            $response = $this->httpClient->request('POST', $url, ['form_params' => $params]);

            if ($response->getStatusCode() === 200) {
                $token = $response->getBody()->getContents();
                $token = json_decode($token, true);
            }
        } catch (GuzzleException $e) {
            $this->logException($e);
        }

        return $token;
    }
    public function getPermissionUser() {
        $userId = \Auth::id();

        if ($permission = session()->get(self::ImapOauth2_SESSION.'user_permission_'.$userId)){
            return $permission;
        }
        $token = $this->retrieveToken();
        $response = \Http::withToken($token['access_token'])->get($this->baseUrl.'/api/permission',['service' => config('app.service_code')]);
        if ($response->successful()) {
            $permission = $response->json();
            session()->put(self::ImapOauth2_SESSION.'user_permission_'.$userId, $permission);
            return $permission;
        }
        return false;
    }
    /**
     * Get access token from Code
     * @param  array $credentials
     * @return array
     */
    public function getUserProfile($credentials)
    {
        $credentials = $this->refreshTokenIfNeeded($credentials);
        if (empty($credentials['access_token'])) {
            $this->forgetToken();
            return [];
        }
        
        $user =  $this->parseAccessToken($credentials['access_token']);

        if (!$user) {
            return [];
        }
        //dd(session()->get(self::ImapOauth2_SESSION.'user_profile_'.$user['sub']));
        if ($userProfile = session()->get(self::ImapOauth2_SESSION.'user_profile_'.$user['sub'])){
            return $userProfile;
        }
        
        
        $userProfile = $this->retrieveProfile($credentials['access_token'], $user);
        if ($userProfile) {
            $userProfile['user_id'] = $user['sub'];
            session()->put(self::ImapOauth2_SESSION.'user_profile_'.$user['sub'], $userProfile);
        }
        return $userProfile;
    }
    public function retrieveProfile($access_token, $user) {

        $profile_url = config('imapoauth.api_microservice_url').'/v1/crm/contacts/search/me';

        $response = \Http::withToken($access_token)->get($profile_url);
       
        if ($response->successful()) {

            return $response->json();
        }
        return false;
    }
    /**
     * Get Access Token data
     *
     * @param string $token
     * @return array
     */
    public function parseAccessToken($token)
    {
    
        if (! is_string($token)) {
            return [];
        }
        $public_key = config('imapoauth.jwt_public_key');  //env('ImapOauth2_JWT_PUBLIC_KEY');
        try {
            JWT::$leeway = 10;
            return (array)JWT::decode($token, $public_key , array('RS256'));
        }catch (\Exception $e) {
             return [];
        }
    }

    /**
     * Retrieve Token from Session
     *
     * @return void
     */
    public function retrieveToken()
    {
        // $a = array_filter([
           
        //     'access_token' => $_COOKIE[self::ImapOauth2_SESSION.'access_token'] ?? '',
        //     'refresh_token' => $_COOKIE[self::ImapOauth2_SESSION.'refresh_token'] ?? '',
        // ]);

        return array_filter([
           
            'access_token' => $_COOKIE[self::ImapOauth2_SESSION.'access_token'] ?? '',
            'refresh_token' => $_COOKIE[self::ImapOauth2_SESSION.'refresh_token'] ?? '',
        ]);
        // return array_filter([
        //     'refresh_token' => Cookie::get(self::ImapOauth2_SESSION.'refresh_token'),
        //     'access_token' => Cookie::get(self::ImapOauth2_SESSION.'access_token'),
        //     //'access_token' => session()->get(self::ImapOauth2_SESSION.'access_token')
        // ]);
        //return session()->get(self::ImapOauth2_SESSION);
    }

    /**
     * Save Token to Session
     *
     * @return void
     */
    public function saveToken($credentials)
    {
    
        //session()->put(self::ImapOauth2_SESSION.'access_token', $credentials['access_token']);
        //Cookie::queue(self::ImapOauth2_SESSION.'access_token', $credentials['access_token'], 43200);
        //Cookie::queue(self::ImapOauth2_SESSION.'refresh_token', $credentials['refresh_token'], 43200);
        
        setcookie(self::ImapOauth2_SESSION.'access_token', $credentials['access_token'], time() + 21600 , '/', null , false , false);
        setcookie(self::ImapOauth2_SESSION.'refresh_token', $credentials['refresh_token'], time() + 259200 , '/', null , false , false); // 3 ngay
        
        //Cookie::queue(cookie(self::ImapOauth2_SESSION.'access_token', $credentials['access_token'], 180, '/' , null , false, false));
        //setcookie("TestCookie", $credentials['access_token'], 180, '/' , null , false, false);
        //cookie('name', 'value', $minutes);
        // session()->put(self::ImapOauth2_SESSION, $credentials);
        // session()->save();
    }

    /**
     * Remove Token from Session
     *
     * @return void
     */
    public function forgetToken()
    {
        //session()->forget(self::ImapOauth2_SESSION.'access_token');
        \Session::invalidate();
        setcookie(self::ImapOauth2_SESSION.'access_token', "", time() - 86400,'/');
        setcookie(self::ImapOauth2_SESSION.'refresh_token', "", time() - 86400,'/');
        // Cookie::queue(Cookie::forget(self::ImapOauth2_SESSION.'refresh_token'));
        // Cookie::queue(Cookie::forget(self::ImapOauth2_SESSION.'access_token'));
    }

    private function getOauthUrl($callbackUrl, $partUrl = 'signup', $loginType = 'app')
    {
        $url = $this->baseUrl.'/'.$partUrl;
        $params = [
            'state' => 'mystate',
            'client_id' => $this->clientId,
            'grant_type'=> 'authorization_code',
            'response_type' => 'code',
            'redirect_uri' => $callbackUrl,
            'login_type' => $loginType
        ];

        return $this->buildUrl($url, $params);
    }

    /**
     * Build a URL with params
     *
     * @param  string $url
     * @param  array $params
     * @return string
     */
    public function buildUrl($url, $params)
    {
        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['host'])) {
            return trim($url, '?') . '?' . Arr::query($params);
        }

        if (! empty($parsedUrl['port'])) {
            $parsedUrl['host'] .= ':' . $parsedUrl['port'];
        }

        $parsedUrl['scheme'] = (empty($parsedUrl['scheme'])) ? 'https' : $parsedUrl['scheme'];
        $parsedUrl['path'] = (empty($parsedUrl['path'])) ? '' : $parsedUrl['path'];

        $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        $query = [];

        if (! empty($parsedUrl['query'])) {
            $parsedUrl['query'] = explode('&', $parsedUrl['query']);

            foreach ($parsedUrl['query'] as $value) {
                $value = explode('=', $value);

                if (count($value) < 2) {
                    continue;
                }

                $key = array_shift($value);
                $value = implode('=', $value);

                $query[$key] = urldecode($value);
            }
        }

        $query = array_merge($query, $params);

        return $url . '?' . Arr::query($query);
    }
    /**
     * Check we need to refresh token and refresh if needed
     *
     * @param  array $credentials
     * @return array
     */
    protected function refreshTokenIfNeeded($credentials)
    {
        if (!empty($credentials['access_token'])) {
            $info = $this->parseAccessToken($credentials['access_token']);
            $exp = $info['exp'] ?? 0;

            if (time() < $exp) {
                return $credentials;
            }
        }
        $credentials = $this->refreshAccessToken($credentials);
        if (empty($credentials['access_token'])) {
            $this->forgetToken();
            return [];
        }
        $this->saveToken($credentials);
        return $credentials;
    }

    /**
     * Log a GuzzleException
     *
     * @param  GuzzleException $e
     * @return void
     */
    protected function logException(GuzzleException $e)
    {

        if (empty($e->getResponse())) {
            Log::error('[ImapOauth2 Service] ' . $e->getMessage());
            return;
        }

        $error = [
            'request' => $e->getRequest(),
            'response' => $e->getResponse()->getBody()->getContents(),
        ];

        Log::error('[ImapOauth2 Service] ' . print_r($error, true));
    }

    /**
     * Base64UrlDecode string
     *
     * @link https://www.php.net/manual/pt_BR/function.base64-encode.php#103849
     *
     * @param  string $data
     * @return string
     */
    protected function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
