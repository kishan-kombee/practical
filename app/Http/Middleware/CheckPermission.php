<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // get controllerclassname@method
        $actionName = class_basename($request->route()->getActionname());
        // separate it from '@' character
        $actionName = explode('@', $actionName);
        // get module name from Controller class e.g. ClientsAPIController -> client
        if (Str::contains($actionName[0], 'APIController')) {
            $module = Str::lower(str_replace('APIController', '', $actionName[0]));
        } else {
            $module = Str::lower(str_replace('sAPIController', '', $actionName[0]));
        }

        $action = $actionName[1];
        $permission = $action . '-' . Str::plural($module);

        $excluded_permissions = [
            'changePassword-logins',
            'getProfileDetails-profiledetails',
            'updateProfileDetails-profiledetails',
            'updateProfileImage-profiledetails',
            'index-notifications',
            'count-notifications',
            'read-notifications',
            'batchRequest-users',
        ];

        // Bypass permission check for batch request endpoints
        if ($request->path() == 'api/v1/batch-request' || $request->path() == 'api/v1/auth-batch-request') {
            return $next($request);
        }

        if (in_array($permission, $excluded_permissions)) {
            return $next($request);
        } elseif ($request->user()->tokenCan($permission)) {// check user permission with his token
            return $next($request);
        } // redirect to requested uri if permitted
        else { // reject the requested uri
            return \Illuminate\Support\Facades\Response::make(config('constants.permission.user_has_not_permission'), config('constants.validation_codes.forbidden'));
        }
    }
}
