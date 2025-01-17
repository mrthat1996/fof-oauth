<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class OAuthServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->tag([
            Providers\Discord::class,
            Providers\Facebook::class,
            Providers\GitHub::class,
            Providers\GitLab::class,
            Providers\Twitter::class,
            Providers\Google::class,
            Providers\LinkedIn::class,
        ], 'fof-oauth.providers');

        // Add OAuth provider routes
        $this->container->resolving('flarum.forum.routes', function (RouteCollection $collection, Container $container) {
            /** @var RouteHandlerFactory $factory */
            $factory = $container->make(RouteHandlerFactory::class);

            $collection->addRoute('GET', new OAuth2RoutePattern(), 'fof-oauth', $factory->toController(Controllers\AuthController::class));
        });

        $this->container->singleton('fof-oauth.providers.forum', $this->map(function (Provider $provider) {
            if (!$provider->enabled()) {
                return null;
            }

            return [
                'name'     => $provider->name(),
                'icon'     => $provider->icon(),
                'priority' => $provider->priority(),
            ];
        }));

        $this->container->singleton('fof-oauth.providers.admin', $this->map(function (Provider $provider) {
            return [
                'name'   => $provider->name(),
                'icon'   => $provider->icon(),
                'link'   => $provider->link(),
                'fields' => $provider->fields(),
            ];
        }));
    }

    protected function map(callable $cb)
    {
        return function () use ($cb) {
            $providers = $this->container->tagged('fof-oauth.providers');

            return array_map(function ($provider) use ($cb) {
                return $cb($provider);
            }, iterator_to_array($providers));
        };
    }
}
