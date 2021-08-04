<?php

namespace ImapOauth2\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use ImapOauth2\Exceptions\ImapOauth2CanException;
use ImapOauth2\Facades\ImapOauth2Web;

class ImapOauth2Can extends ImapOauth2Authenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $allowed_permissions = ImapOauth2Web::getPermissionUser();
        if (!$allowed_permissions) {
            abort(403);
        }
        $is_superadmin = (!empty($allowed_permissions['role']) && $allowed_permissions['role'] == 'superadmin') ? true : false;
        $current_nameas = \Request::route()->getName(); //router name
        \Gate::before(function () use ($is_superadmin) {
            //return true;
            if($is_superadmin){
                return $is_superadmin;
            }
        });
        if(!empty($allowed_permissions)){
            \Gate::define('home', function ($user) {
                return true;
            });
            foreach($allowed_permissions as $allowed_permission) {
                \Gate::define($allowed_permission, function ($user) {
                    return true;
                });
            }
            
        }
        if(\Gate::allows($current_nameas)){
            return $next($request);
        }

        abort(403);
    }
}
