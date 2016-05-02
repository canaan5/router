<?php
/**
 * Created by Canan Etaigbenu
 * User: canaan5
 * Date: 3/28/16
 * Time: 11:58 PM.
 */

namespace Innovating\Routing;

use Innovating\Http\Request;
use Innovating\Routing\Contracts\RouteCollectionContract;
use Innovating\Routing\Contracts\RouteContract;
use Innovating\Routing\Exceptions\RouteNotFoundException;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route as SyfonyRoute;

class RouteCollection implements RouteCollectionContract
{
    /**
     * Request instance from the container.
     *
     * @var Request
     */
    protected $request;

    /**
     * current route uri.
     *
     * @var string
     */
    protected $uri;

    /**
     * symfony compiled route.
     *
     * @var CompiledRoute
     */
    protected $compiled;

    /**
     * the current route that matches the request.
     *
     * @var array
     */
    protected $currentRoute = [];

    /**
     * the current route parameters that matches the request.
     *
     * @var array
     */
    protected $currentParams = [];

    public function __construct($container)
    {
        $this->request = $container->request ?: \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }

    /**
     * Add a route to the route collection.
     *
     * @param $route
     */
    public function add(RouteContract $route)
    {
        $this->addCurrentRouteAndParams($route);
    }

    /**
     * process the route and request and save them.
     *
     * @param Route $route
     *
     * @return Route
     */
    public function addCurrentRouteAndParams(RouteContract $route)
    {
        $this->uri = $route->getUri();
        $uri = preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->uri);

        /*
         * compile the route with symfony Route Compiler
         */
        $this->compiled = (new SyfonyRoute($uri))->compile();

        /*
         * Matches the current request against the compile route RegEx.
         *
         */

        // decoded request path
        $path = $this->request->createFromGlobals()->decodedPath();

        $decodedPath = ("/" === $path) ? $path : '/'.$path;

        if (preg_match($this->compiled->getRegex(), $decodedPath, $matches)) {

            /*
             * extract the parameters and set them to the current route parameters
             */
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    array_push($this->currentParams, $value);
                }
            }

            /*
             * Set the current route to the route
             */
            $this->currentRoute[$route->getMethods()][$decodedPath] = $route;

            /*
             * add the current route parameters to the current route
             */
            $this->currentRoute[$route->getMethods()][$decodedPath]->setParameters($this->currentParams);

            return $route;
        }

        /*
         * if its a simple route that matches the request, set it to the route and return it
         */
        if ($uri == $decodedPath) {
            if (sizeof($route->getMethods()) > 1) {
                foreach ($route->getMethods() as $method) {
                    $this->currentRoute[$method][$uri] = $route;
                }
            } else {
                $this->currentRoute[$route->getMethods()][$uri] = $route;
            }
        }
    }

    /**
     * match a route against the current request.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function match(RequestInterface $request)
    {
        $rMethod = $request->getMethod();
        $requestPath = $request->path() == '/' ? '/' : '/'.$request->decodedPath();

        /*
         * check if any defined route matches the request path, if theres none, throw and exception
         */

        if (empty($this->currentRoute)) {
            throw new RouteNotFoundException(sprintf('the Route [%s] has not been defined', $requestPath));
        }

        /*
         * return the matched route for the dispatcher to consume
         */
        return $this->currentRoute[$rMethod][$requestPath];
    }
}
