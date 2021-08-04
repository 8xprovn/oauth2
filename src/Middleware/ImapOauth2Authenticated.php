<?php

namespace ImapOauth2\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use ImapOauth2\Facades\ImapOauth2Web;

class ImapOauth2Authenticated extends Authenticate
{
    /**
     * Redirect user if it's not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        $url = ImapOauth2Web::getLoginUrl();
        return $url;
        //return redirect($url);
    }
}
