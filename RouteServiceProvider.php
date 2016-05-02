<?php
/**
 * Created by Canan Etaigbenu
 * User: canaan5
 * Date: 3/26/16
 * Time: 9:33 PM.
 */

namespace Innovating\Routing;

use Innovating\Provider\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register a service provider.
     */
    public function register()
    {
        /*
         * @return \Canaan5\Routing\Router
         */
        $this->app['router'] = $this->app->share(function ($app) {
            return new Router($app);
        });
    }
}
