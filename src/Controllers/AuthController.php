<?php

namespace ImapOauth2\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use ImapOauth2\Exceptions\ImapOauth2CallbackException;
use ImapOauth2\Facades\ImapOauth2Web;
use ImapOauth2\Facades\ImapGuard;

class AuthController extends Controller
{
    /**
     * Redirect to login
     *
     * @return view
     */
    public function login()
    {
        $url = ImapOauth2Web::getLoginUrl();
        return redirect($url);
    }

    /**
     * Redirect to logout
     *
     * @return view
     */
    public function logout()
    {
        ImapOauth2Web::forgetToken();

        $url = ImapOauth2Web::getLogoutUrl();
        return redirect($url);
    }

    /**
     * Redirect to register
     *
     * @return view
     */
    public function register()
    {
        $url = ImapOauth2Web::getRegisterUrl();
        return redirect($url);
    }

    /**
     * ImapOauth2 callback page
     *
     * @throws ImapOauth2CallbackException
     *
     * @return view
     */
    public function callback(Request $request)
    {
        if (! empty($request->input('error'))) {
            $error = $request->input('error_description');
            $error = ($error) ?: $request->input('error');

            throw new ImapOauth2CallbackException($error);
        }

        $code = $request->input('code');
        if (! empty($code)) {
            $token = ImapOauth2Web::getAccessToken($code);
            if (ImapGuard::validate($token)) {
                $url = env('ROUTE_PREFIX') ?? '/';
                return redirect($url);
            }
        }

        return redirect(route('ImapOauth2.logout'));
    }

    /**
     * Redirect to login google
     *
     * @return view
     */
    public function googleLogin()
    {
        $url = ImapOauth2Web::getOauthGoogleUrl();
        return redirect($url);
    }

    
    /**
     * ImapOauth2 callback page
     *
     * @throws ImapOauth2CallbackException
     *
     * @return view
     */
    public function googleCallback(Request $request)
    {
        if (! empty($request->input('error'))) {
            $error = $request->input('error_description');
            $error = ($error) ?: $request->input('error');

            throw new ImapOauth2CallbackException($error);
        }

        $code = $request->input('code');
       
        if (! empty($code)) {
            $token = ImapOauth2Web::getAccessToken($code, ImapOauth2Web::getGoogleUrlCallback());
            if (ImapGuard::validate($token)) {
                $url = env('ROUTE_PREFIX') ?? '/';
                //dd(ImapGuard::user());
                return redirect($url);
            }
        }

        //return redirect(route('ImapOauth2.logout'));
    }

    /**
     * Redirect to login facebook
     *
     * @return view
     */
    public function facebookLogin()
    {
        $url = ImapOauth2Web::getOauthFacebookUrl();
        return redirect($url);
    }

    
    /**
     * ImapOauth2 callback page
     *
     * @throws ImapOauth2CallbackException
     *
     * @return view
     */
    public function facebookCallback(Request $request)
    {
        if (! empty($request->input('error'))) {
            $error = $request->input('error_description');
            $error = ($error) ?: $request->input('error');

            throw new ImapOauth2CallbackException($error);
        }

        $code = $request->input('code');
        if (! empty($code)) {
            $token = ImapOauth2Web::getAccessToken($code, ImapOauth2Web::getFacebookUrlCallback());
            if (ImapGuard::validate($token)) {
                $url = env('ROUTE_PREFIX') ?? '/';
                return redirect($url);
            }
        }

        return redirect(route('ImapOauth2.logout'));
    }
}
