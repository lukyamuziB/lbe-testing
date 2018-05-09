<?php

namespace App\Providers;

use App\Models\User;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest(
            'api',
            function ($request) {
                if ($request->path() === "/") {
                    return;
                } else {
                    $token = substr($request->header('Authorization'), 7);
                    $parsed_token = (new Parser())->parse((string) $token);

                    // use the factory to generate the user instance from token payload
                    if ($request->input('api_token') || $token) {
                        $user = factory(\App\Models\User::class)->make(
                            [
                                'uid' => $parsed_token->getClaim('UserInfo')->id,
                                'name' => $parsed_token->getClaim('UserInfo')->first_name." ".$parsed_token->getClaim('UserInfo')->last_name,
                                'email' => $parsed_token->getClaim('UserInfo')->email,
                                'roles' => array_keys((array)$parsed_token->getClaim('UserInfo')->roles),
                                'firstname' => $parsed_token->getClaim('UserInfo')->first_name,
                                'lastname' => $parsed_token->getClaim('UserInfo')->last_name,
                                'profile_pic' => $parsed_token->getClaim('UserInfo')->picture,
                            ]
                        );

                        return $user;
                    }
                }
            }
        );
    }
}
