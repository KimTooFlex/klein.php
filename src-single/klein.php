<?php
/* This is an auto-generated file. Do not edit */

namespace Klein {
use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use Klein\DataCollection\DataCollection;
use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\DataCollection\RouteCollection;
use Klein\DataCollection\ServerDataCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\DuplicateServiceException;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\ResponseAlreadySentException;
use Klein\Exceptions\UnhandledException;
use Klein\Exceptions\UnknownServiceException;
use Klein\Exceptions\ValidationException;
use Klein\ResponseCookie;
use OutOfBoundsException;

/* Start of src/Klein/App.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */








/**
 * App 
 * 
 * @package    Klein
 */
class App
{

    /**
     * Class properties
     */

    /**
     * The array of app services
     *
     * @var array
     * @access protected
     */
    protected $services = array();

    /**
     * Magic "__get" method
     *
     * Allows the ability to arbitrarily request a service from this instance
     * while treating it as an instance property
     *
     * This checks the lazy service register and automatically calls the registered
     * service method
     *
     * @param string $name              The name of the service
     * @throws UnknownServiceException  If a non-registered service is attempted to fetched
     * @access public
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->services[$name])) {
            throw new UnknownServiceException('Unknown service '. $name);
        }
        $service = $this->services[$name];

        return $service();
    }

    /**
     * Magic "__call" method
     *
     * Allows the ability to arbitrarily call a property as a callable method
     * Allow callbacks to be assigned as properties and called like normal methods
     *
     * @param callable $method          The callable method to execute
     * @param array $args               The argument array to pass to our callback
     * @throws BadMethodCallException   If a non-registered method is attempted to be called
     * @access public
     * @return void
     */
    public function __call($method, $args)
    {
        if (!isset($this->services[$method]) || !is_callable($this->services[$method])) {
            throw new BadMethodCallException('Unknown method '. $method .'()');
        }

        return call_user_func_array($this->services[$method], $args);
    }

    /**
     * Register a lazy service
     *
     * @param string $name                  The name of the service
     * @param callable $closure             The callable function to execute when requesting our service
     * @throws DuplicateServiceException    If an attempt is made to register two services with the same name
     * @access public
     * @return mixed
     */
    public function register($name, $closure)
    {
        if (isset($this->services[$name])) {
            throw new DuplicateServiceException('A service is already registered under '. $name);
        }

        $this->services[$name] = function () use ($closure) {
            static $instance;
            if (null === $instance) {
                $instance = $closure();
            }

            return $instance;
        };
    }
}


/* End of src/Klein/App.php */

/* -------------------- */

/* Start of src/Klein/Route.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * Route
 *
 * Class to represent a route definition
 *
 * @package     Klein
 */
class Route
{

    /**
     * Properties
     */

    /**
     * The callback method to execute when the route is matched
     *
     * Any valid "callable" type is allowed
     *
     * @link http://php.net/manual/en/language.types.callable.php
     * @var callable
     * @access protected
     */
    protected $callback;

    /**
     * The URL path to match
     *
     * Allows for regular expression matching and/or basic string matching
     *
     * Examples:
     * - '/posts'
     * - '/posts/[:post_slug]'
     * - '/posts/[i:id]'
     *
     * @var string
     * @access protected
     */
    protected $path;

    /**
     * The HTTP method to match
     *
     * May either be represented as a string or an array containing multiple methods to match
     *
     * Examples:
     * - 'POST'
     * - array('GET', 'POST')
     *
     * @var string|array
     * @access protected
     */
    protected $method;

    /**
     * Whether or not to count this route as a match when counting total matches
     *
     * @var boolean
     * @access protected
     */
    protected $count_match;

    /**
     * The name of the route
     *
     * Mostly used for reverse routing
     *
     * @var string
     * @access protected
     */
    protected $name;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param callable $callback
     * @param string $path
     * @param string|array $method
     * @param boolean $count_match
     * @access public
     */
    public function __construct($callback, $path = null, $method = null, $count_match = true, $name = null)
    {
        // Initialize some properties (use our setters so we can validate param types)
        $this->setCallback($callback);
        $this->setPath($path);
        $this->setMethod($method);
        $this->setCountMatch($count_match);
        $this->setName($name);
    }

    /**
     * Get the callback
     *
     * @access public
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }
    
    /**
     * Set the callback
     *
     * @param callable $callback
     * @throws InvalidArgumentException If the callback isn't a callable
     * @access public
     * @return Route
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expected a callable. Got an uncallable '. gettype($callback));
        }

        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the path
     *
     * @access public
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Set the path
     *
     * @param string $path
     * @access public
     * @return Route
     */
    public function setPath($path)
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Get the method
     *
     * @access public
     * @return string|array
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Set the method
     *
     * @param string|array $method
     * @throws InvalidArgumentException If a non-string or non-array type is passed
     * @access public
     * @return Route
     */
    public function setMethod($method)
    {
        // Allow null, otherwise expect an array or a string
        if (null !== $method && !is_array($method) && !is_string($method)) {
            throw new InvalidArgumentException('Expected an array or string. Got a '. gettype($method));
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Get the count_match
     *
     * @access public
     * @return boolean
     */
    public function getCountMatch()
    {
        return $this->count_match;
    }
    
    /**
     * Set the count_match
     *
     * @param boolean $count_match
     * @access public
     * @return Route
     */
    public function setCountMatch($count_match)
    {
        $this->count_match = (boolean) $count_match;

        return $this;
    }

    /**
     * Get the name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the name
     *
     * @param string $name
     * @access public
     * @return Route
     */
    public function setName($name)
    {
        if (null !== $name) {
            $this->name = (string) $name;
        } else {
            $this->name = $name;
        }

        return $this;
    }


    /**
     * Magic "__invoke" method
     *
     * Allows the ability to arbitrarily call this instance like a function
     *
     * @param mixed $args Generic arguments, magically accepted
     * @access public
     * @return mixed
     */
    public function __invoke($args = null)
    {
        $args = func_get_args();

        return call_user_func_array(
            $this->callback,
            $args
        );
    }
}


/* End of src/Klein/Route.php */

/* -------------------- */

/* Start of src/Klein/Klein.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */











/**
 * Klein
 *
 * Main Klein router class
 * 
 * @package     Klein
 */
class Klein
{

    /**
     * Class constants
     */

    /**
     * The regular expression used to compile and match URL's
     *
     * @const string
     */
    const ROUTE_COMPILE_REGEX = '`(\\\?(?:/|\.|))(\[([^:\]]*+)(?::([^:\]]*+))?\])(\?|)`';

    /**
     * The regular expression used to escape the non-named param section of a route URL
     *
     * @const string
     */
    const ROUTE_ESCAPE_REGEX = '`(?<=^|\])[^\]\[\?]+?(?=\[|$)`';

    /**
     * Dispatch route output handling
     *
     * Don't capture anything. Behave as normal.
     *
     * @const int
     */
    const DISPATCH_NO_CAPTURE = 0;

    /**
     * Dispatch route output handling
     *
     * Capture all output and return it from dispatch
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_RETURN = 1;

    /**
     * Dispatch route output handling
     *
     * Capture all output and replace the response body with it
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_REPLACE = 2;

    /**
     * Dispatch route output handling
     *
     * Capture all output and prepend it to the response body
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_PREPEND = 3;

    /**
     * Dispatch route output handling
     *
     * Capture all output and append it to the response body
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_APPEND = 4;


    /**
     * Class properties
     */

    /**
     * Collection of the routes to match on dispatch
     *
     * @var RouteCollection
     * @access protected
     */
    protected $routes;

    /**
     * The Route factory object responsible for creating Route instances
     *
     * @var AbstractRouteFactory
     * @access protected
     */
    protected $route_factory;

    /**
     * An array of error callback callables
     *
     * @var array[callable]
     * @access protected
     */
    protected $errorCallbacks = array();

    /**
     * An array of callbacks to call after processing the dispatch loop
     * and before the response is sent
     *
     * @var array[callable]
     * @access protected
     */
    protected $afterFilterCallbacks = array();


    /**
     * Route objects
     */

    /**
     * The Request object passed to each matched route
     *
     * @var Request
     * @access protected
     */
    protected $request;

    /**
     * The Response object passed to each matched route
     *
     * @var Response
     * @access protected
     */
    protected $response;

    /**
     * The service provider object passed to each matched route
     *
     * @var ServiceProvider
     * @access protected
     */
    protected $service;

    /**
     * A generic variable passed to each matched route
     *
     * @var mixed
     * @access protected
     */
    protected $app;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * Create a new Klein instance with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param ServiceProvider $service              Service provider object responsible for utilitarian behaviors
     * @param mixed $app                            An object passed to each route callback, defaults to an App instance
     * @param RouteCollection $routes               Collection object responsible for containing all route instances
     * @param AbstractRouteFactory $route_factory   A factory class responsible for creating Route instances
     * @access public
     */
    public function __construct(
        ServiceProvider $service = null,
        $app = null,
        RouteCollection $routes = null,
        AbstractRouteFactory $route_factory = null
    ) {
        // Instanciate and fall back to defaults
        $this->service       = $service       ?: new ServiceProvider();
        $this->app           = $app           ?: new App();
        $this->routes        = $routes        ?: new RouteCollection();
        $this->route_factory = $route_factory ?: new RouteFactory();
    }

    /**
     * Returns the routes object
     *
     * @access public
     * @return RouteCollection
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * Returns the request object
     *
     * @access public
     * @return Request
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Returns the response object
     *
     * @access public
     * @return Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Returns the service object
     *
     * @access public
     * @return ServiceProvider
     */
    public function service()
    {
        return $this->service;
    }

    /**
     * Returns the app object
     *
     * @access public
     * @return mixed
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * Parse our extremely loose argument order of our "respond" method and its aliases
     *
     * This method takes its arguments in a loose format and order.
     * The method signature is simply there for documentation purposes, but allows
     * for the minimum of a callback to be passed in its current configuration.
     *
     * @see Klein::respond()
     * @param mixed $args               An argument array. Hint: This works well when passing "func_get_args()"
     *  @named string | array $method   HTTP Method to match
     *  @named string $path             Route URI path to match
     *  @named callable $callback       Callable callback method to execute on route match
     * @access protected
     * @return array                    A named parameter array containing the keys: 'method', 'path', and 'callback'
     */
    protected function parseLooseArgumentOrder(array $args)
    {
        // Get the arguments in a very loose format
        $callback = array_pop($args);
        $path = array_pop($args);
        $method = array_pop($args);

        // Return a named parameter array
        return array(
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
        );
    }

    /**
     * Add a new route to be matched on dispatch
     *
     * Essentially, this method is a standard "Route" builder/factory,
     * allowing a loose argument format and a standard way of creating
     * Route instances
     *
     * This method takes its arguments in a very loose format
     * The only "required" parameter is the callback (which is very strange considering the argument definition order)
     *
     * <code>
     * $router = new Klein();
     *
     * $router->respond( function() {
     *     echo 'this works';
     * });
     * $router->respond( '/endpoint', function() {
     *     echo 'this also works';
     * });
     * $router->respond( 'POST', '/endpoint', function() {
     *     echo 'this also works!!!!';
     * });
     * </code>
     *
     * @param string | array $method    HTTP Method to match
     * @param string $path              Route URI path to match
     * @param callable $callback        Callable callback method to execute on route match
     * @access public
     * @return callable $callback
     */
    public function respond($method, $path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        $route = $this->route_factory->build($callback, $path, $method);

        $this->routes->add($route);

        return $route;
    }

    /**
     * Collect a set of routes under a common namespace
     *
     * The routes may be passed in as either a callable (which holds the route definitions),
     * or as a string of a filename, of which to "include" under the Klein router scope
     *
     * <code>
     * $router = new Klein();
     *
     * $router->with('/users', function($router) {
     *     $router->respond( '/', function() {
     *         // do something interesting
     *     });
     *     $router->respond( '/[i:id]', function() {
     *         // do something different
     *     });
     * });
     *
     * $router->with('/cars', __DIR__ . '/routes/cars.php');
     * </code>
     *
     * @param string $namespace                     The namespace under which to collect the routes
     * @param callable | string[filename] $routes   The defined routes to collect under the namespace
     * @access public
     * @return void
     */
    public function with($namespace, $routes)
    {
        $previous = $this->route_factory->getNamespace();

        $this->route_factory->appendNamespace($namespace);

        if (is_callable($routes)) {
            $routes($this);
        } else {
            require $routes;
        }

        $this->route_factory->setNamespace($previous);
    }

    /**
     * Dispatch the request to the approriate route(s)
     *
     * Dispatch with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param Request $request              The request object to give to each callback
     * @param AbstractResponse $response    The response object to give to each callback
     * @param boolean $send_response        Whether or not to "send" the response after the last route has been matched
     * @param int $capture                  Specify a DISPATCH_* constant to change the output capturing behavior
     * @access public
     * @return void|string
     */
    public function dispatch(
        Request $request = null,
        AbstractResponse $response = null,
        $send_response = true,
        $capture = self::DISPATCH_NO_CAPTURE
    ) {
        // Set/Initialize our objects to be sent in each callback
        $this->request = $request ?: Request::createFromGlobals();
        $this->response = $response ?: new Response();

        // Bind our objects to our service
        $this->service->bind($this->request, $this->response);

        // Prepare any named routes
        $this->routes->prepareNamed();


        // Grab some data from the request
        $uri = $this->request->pathname();
        $req_method = $this->request->method();

        // Set up some variables for matching
        $skip_num = 0;
        $matched = $this->routes->cloneEmpty(); // Get a clone of the routes collection, as it may have been injected
        $methods_matched = array();
        $params = array();
        $apc = function_exists('apc_fetch');

        ob_start();

        foreach ($this->routes as $route) {
            // Are we skipping any matches?
            if ($skip_num > 0) {
                $skip_num--;
                continue;
            }

            // Grab the properties of the route handler
            $method = $route->getMethod();
            $path = $route->getPath();
            $count_match = $route->getCountMatch();

            // Keep track of whether this specific request method was matched
            $method_match = null;

            // Was a method specified? If so, check it against the current request method
            if (is_array($method)) {
                foreach ($method as $test) {
                    if (strcasecmp($req_method, $test) === 0) {
                        $method_match = true;
                    } elseif (strcasecmp($req_method, 'HEAD') === 0
                          && (strcasecmp($test, 'HEAD') === 0 || strcasecmp($test, 'GET') === 0)) {

                        // Test for HEAD request (like GET)
                        $method_match = true;
                    }
                }

                if (null === $method_match) {
                    $method_match = false;
                }
            } elseif (null !== $method && strcasecmp($req_method, $method) !== 0) {
                $method_match = false;

                // Test for HEAD request (like GET)
                if (strcasecmp($req_method, 'HEAD') === 0
                    && (strcasecmp($method, 'HEAD') === 0 || strcasecmp($method, 'GET') === 0 )) {

                    $method_match = true;
                }
            } elseif (null !== $method && strcasecmp($req_method, $method) === 0) {
                $method_match = true;
            }

            // If the method was matched or if it wasn't even passed (in the route callback)
            $possible_match = (null === $method_match) || $method_match;

            // ! is used to negate a match
            if (isset($path[0]) && $path[0] === '!') {
                $negate = true;
                $i = 1;
            } else {
                $negate = false;
                $i = 0;
            }

            // Check for a wildcard (match all)
            if ($path === '*') {
                $match = true;

            } elseif (($path === '404' && $matched->isEmpty() && count($methods_matched) <= 0)
                   || ($path === '405' && $matched->isEmpty() && count($methods_matched) > 0)) {

                // Easily handle 40x's

                $this->handleRouteCallback($route, $matched, $methods_matched);

                continue;

            } elseif (isset($path[$i]) && $path[$i] === '@') {
                // @ is used to specify custom regex

                $match = preg_match('`' . substr($path, $i + 1) . '`', $uri, $params);

            } else {
                // Compiling and matching regular expressions is relatively
                // expensive, so try and match by a substring first

                $expression = null;
                $regex = false;
                $j = 0;
                $n = isset($path[$i]) ? $path[$i] : null;

                // Find the longest non-regex substring and match it against the URI
                while (true) {
                    if (!isset($path[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($path[$i+1])) {
                            $n = $path[$i + 1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }
                        if (false === $regex && $c !== '/' && (!isset($uri[$j]) || $c !== $uri[$j])) {
                            continue 2;
                        }
                        $j++;
                    }
                    $expression .= $path[$i++];
                }

                // Check if there's a cached regex string
                if (false !== $apc) {
                    $regex = apc_fetch("route:$expression");
                    if (false === $regex) {
                        $regex = $this->compileRoute($expression);
                        apc_store("route:$expression", $regex);
                    }
                } else {
                    $regex = $this->compileRoute($expression);
                }

                $match = preg_match($regex, $uri, $params);
            }

            if (isset($match) && $match ^ $negate) {
                if ($possible_match) {
                    if (!empty($params)) {
                        /**
                         * URL Decode the params according to RFC 3986
                         * @link http://www.faqs.org/rfcs/rfc3986
                         *
                         * Decode here AFTER matching as per @chriso's suggestion
                         * @link https://github.com/chriso/klein.php/issues/117#issuecomment-21093915
                         */
                        $params = array_map('rawurldecode', $params);

                        $this->request->paramsNamed()->merge($params);
                    }

                    // Handle our response callback
                    try {
                        $this->handleRouteCallback($route, $matched, $methods_matched);

                    } catch (DispatchHaltedException $e) {
                        switch ($e->getCode()) {
                            case DispatchHaltedException::SKIP_THIS:
                                continue 2;
                                break;
                            case DispatchHaltedException::SKIP_NEXT:
                                $skip_num = $e->getNumberOfSkips();
                                break;
                            case DispatchHaltedException::SKIP_REMAINING:
                                break 2;
                            default:
                                throw $e;
                        }
                    }

                    if ($path !== '*') {
                        $count_match && $matched->add($route);
                    }
                }

                // Keep track of possibly matched methods
                $methods_matched = array_merge($methods_matched, (array) $method);
                $methods_matched = array_filter($methods_matched);
                $methods_matched = array_unique($methods_matched);
            }
        }

        try {
            if ($matched->isEmpty() && count($methods_matched) > 0) {
                if (strcasecmp($req_method, 'OPTIONS') !== 0) {
                    $this->response->code(405);
                }

                $this->response->header('Allow', implode(', ', $methods_matched));
            } elseif ($matched->isEmpty()) {
                $this->response->code(404);
            }

            if ($this->response->chunked) {
                $this->response->chunk();

            } else {
                // Output capturing behavior
                switch($capture) {
                    case self::DISPATCH_CAPTURE_AND_RETURN:
                        return ob_get_clean();
                        break;
                    case self::DISPATCH_CAPTURE_AND_REPLACE:
                        $this->response->body(ob_get_clean());
                        break;
                    case self::DISPATCH_CAPTURE_AND_PREPEND:
                        $this->response->prepend(ob_get_clean());
                        break;
                    case self::DISPATCH_CAPTURE_AND_APPEND:
                        $this->response->append(ob_get_clean());
                        break;
                    case self::DISPATCH_NO_CAPTURE:
                    default:
                        ob_end_flush();
                }
            }

            // Test for HEAD request (like GET)
            if (strcasecmp($req_method, 'HEAD') === 0) {
                // HEAD requests shouldn't return a body
                $this->response->body('');

                if (ob_get_level()) {
                    ob_clean();
                }
            }
        } catch (LockedResponseException $e) {
            // Do nothing, since this is an automated behavior
        }

        // Run our after dispatch callbacks
        $this->callAfterDispatchCallbacks();

        if ($send_response && !$this->response->isSent()) {
            $this->response->send();
        }
    }

    /**
     * Compiles a route string to a regular expression
     *
     * @param string $route     The route string to compile
     * @access protected
     * @return void
     */
    protected function compileRoute($route)
    {
        // First escape all of the non-named param (non [block]s) for regex-chars
        if (preg_match_all(static::ROUTE_ESCAPE_REGEX, $route, $escape_locations, PREG_SET_ORDER)) {
            foreach ($escape_locations as $locations) {
                $route = str_replace($locations[0], preg_quote($locations[0]), $route);
            }
        }

        // Now let's actually compile the path
        if (preg_match_all(static::ROUTE_COMPILE_REGEX, $route, $matches, PREG_SET_ORDER)) {
            $match_types = array(
                'i'  => '[0-9]++',
                'a'  => '[0-9A-Za-z]++',
                'h'  => '[0-9A-Fa-f]++',
                's'  => '[0-9A-Za-z-_]++',
                '*'  => '.+?',
                '**' => '.++',
                ''   => '[^/]+?'
            );

            foreach ($matches as $match) {
                list($block, $pre, $inner_block, $type, $param, $optional) = $match;

                if (isset($match_types[$type])) {
                    $type = $match_types[$type];
                }
                // Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                         . ($pre !== '' ? $pre : null)
                         . '('
                         . ($param !== '' ? "?P<$param>" : null)
                         . $type
                         . '))'
                         . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`";
    }

    /**
     * Get the path for a given route
     *
     * This looks up the route by its passed name and returns
     * the path/url for that route, with its URL params as
     * placeholders unless you pass a valid key-value pair array
     * of the placeholder params and their values
     *
     * If a pathname is a complex/custom regular expression, this
     * method will simply return the regular expression used to
     * match the request pathname, unless an optional boolean is
     * passed "flatten_regex" which will flatten the regular
     * expression into a simple path string
     *
     * This method, and its style of reverse-compilation, was originally
     * inspired by a similar effort by Gilles Bouthenot (@gbouthenot)
     *
     * @link https://github.com/gbouthenot
     * @param string $route_name        The name of the route
     * @param array $params             The array of placeholder fillers
     * @param boolean $flatten_regex    Optionally flatten custom regular expressions to "/"
     * @throws OutOfBoundsException     If the route requested doesn't exist
     * @access public
     * @return string
     */
    public function getPathFor($route_name, array $params = null, $flatten_regex = true)
    {
        // First, grab the route
        $route = $this->routes->get($route_name);

        // Make sure we are getting a valid route
        if (null === $route) {
            throw new OutOfBoundsException('No such route with name: '. $route_name);
        }

        $path = $route->getPath();

        if (preg_match_all(static::ROUTE_COMPILE_REGEX, $path, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                list($block, $pre, $inner_block, $type, $param, $optional) = $match;

                if (isset($params[$param])) {
                    $path = str_replace($block, $pre. $params[$param], $path);
                } elseif ($optional) {
                    $path = str_replace($block, '', $path);
                }
            }

        } elseif ($flatten_regex && strpos($path, '@') === 0) {
            // If the path is a custom regular expression and we're "flattening", just return a slash
            $path = '/';
        }

        return $path;
    }

    /**
     * Handle a route's callback
     *
     * This handles common exceptions and their output
     * to keep the "dispatch()" method DRY
     *
     * @param Route $route
     * @param RouteCollection $matched
     * @param int $methods_matched
     * @access protected
     * @return void
     */
    protected function handleRouteCallback(Route $route, RouteCollection $matched, $methods_matched)
    {
        // Handle the callback
        try {
            $returned = call_user_func(
                $route->getCallback(), // Instead of relying on the slower "invoke" magic
                $this->request,
                $this->response,
                $this->service,
                $this->app,
                $this, // Pass the Klein instance
                $matched,
                $methods_matched
            );

            if ($returned instanceof AbstractResponse) {
                $this->response = $returned;
            } else {
                // Otherwise, attempt to append the returned data
                try {
                    $this->response->append($returned);
                } catch (LockedResponseException $e) {
                    // Do nothing, since this is an automated behavior
                }
            }
        } catch (DispatchHaltedException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    /**
     * Adds an error callback to the stack of error handlers
     *
     * @param callable $callback            The callable function to execute in the error handling chain
     * @access public
     * @return boolean|void
     */
    public function onError($callback)
    {
        $this->errorCallbacks[] = $callback;
    }

    /**
     * Routes an exception through the error callbacks
     *
     * @param Exception $err        The exception that occurred
     * @throws UnhandledException   If the error/exception isn't handled by an error callback
     * @access protected
     * @return void
     */
    protected function error(Exception $err)
    {
        $type = get_class($err);
        $msg = $err->getMessage();

        if (count($this->errorCallbacks) > 0) {
            foreach (array_reverse($this->errorCallbacks) as $callback) {
                if (is_callable($callback)) {
                    if (is_string($callback)) {
                        $callback($this, $msg, $type, $err);

                        return;
                    } else {
                        call_user_func($callback, $this, $msg, $type, $err);

                        return;
                    }
                } else {
                    if (null !== $this->service && null !== $this->response) {
                        $this->service->flash($err);
                        $this->response->redirect($callback);
                    }
                }
            }
        } else {
            $this->response->code(500);
            throw new UnhandledException($err);
        }
    }

    /**
     * Adds a callback to the stack of handlers to run after the dispatch
     * loop has handled all of the route callbacks and before the response
     * is sent
     *
     * @param callable $callback            The callable function to execute in the after route chain
     * @access public
     * @return void
     */
    public function afterDispatch($callback)
    {
        $this->afterFilterCallbacks[] = $callback;
    }

    /**
     * Runs through and executes the after dispatch callbacks
     *
     * @access protected
     * @return void
     */
    protected function callAfterDispatchCallbacks()
    {
        try {
            foreach ($this->afterFilterCallbacks as $callback) {
                if (is_callable($callback)) {
                    if (is_string($callback)) {
                        $callback($this);

                    } else {
                        call_user_func($callback, $this);

                    }
                }
            }
        } catch (Exception $e) {
            $this->error($e);
        }
    }


    /**
     * Method aliases
     */

    /**
     * Quick alias to skip the current callback/route method from executing
     *
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     * @access public
     * @return void
     */
    public function skipThis()
    {
        throw new DispatchHaltedException(null, DispatchHaltedException::SKIP_THIS);
    }

    /**
     * Quick alias to skip the next callback/route method from executing
     *
     * @param int $num The number of next matches to skip
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     * @access public
     * @return void
     */
    public function skipNext($num = 1)
    {
        $skip = new DispatchHaltedException(null, DispatchHaltedException::SKIP_NEXT);
        $skip->setNumberOfSkips($num);

        throw $skip;
    }

    /**
     * Quick alias to stop the remaining callbacks/route methods from executing
     *
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     * @access public
     * @return void
     */
    public function skipRemaining()
    {
        throw new DispatchHaltedException(null, DispatchHaltedException::SKIP_REMAINING);
    }

    /**
     * Alias to set a response code, lock the response, and halt the route matching/dispatching
     *
     * @param int $code     Optional HTTP status code to send
     * @throws DispatchHaltedException To halt/skip the current dispatch loop
     * @access public
     * @return void
     */
    public function abort($code = null)
    {
        if (null !== $code) {
            $this->response->code($code);
        }

        // Disallow further response modification
        $this->response->lock();

        throw new DispatchHaltedException();
    }

    /**
     * OPTIONS alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $route
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function options($path = '*', $callback = null)
    {
        // Options the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('OPTIONS', $path, $callback);
    }

    /**
     * HEAD alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function head($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('HEAD', $path, $callback);
    }

    /**
     * GET alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $route
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function get($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('GET', $path, $callback);
    }

    /**
     * POST alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function post($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('POST', $path, $callback);
    }

    /**
     * PUT alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function put($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('PUT', $path, $callback);
    }

    /**
     * DELETE alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @access public
     * @return callable
     */
    public function delete($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('DELETE', $path, $callback);
    }
}


/* End of src/Klein/Klein.php */

/* -------------------- */

/* Start of src/Klein/Request.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */







/**
 * Request
 * 
 * @package     Klein
 */
class Request
{

    /**
     * Class properties
     */

    /**
     * Unique identifier for the request
     *
     * @var string
     * @access protected
     */
    protected $id;

    /**
     * GET (query) parameters
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $params_get;

    /**
     * POST parameters
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $params_post;

    /**
     * Named parameters
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $params_named;

    /**
     * Client cookie data
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $cookies;

    /**
     * Server created attributes
     *
     * @var \Klein\DataCollection\ServerDataCollection
     * @access protected
     */
    protected $server;

    /**
     * HTTP request headers
     *
     * @var \Klein\DataCollection\HeaderDataCollection
     * @access protected
     */
    protected $headers;

    /**
     * Uploaded temporary files
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $files;

    /**
     * The request body
     *
     * @var string
     * @access protected
     */
    protected $body;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * Create a new Request object and define all of its request data
     *
     * @param array  $params_get
     * @param array  $params_post
     * @param array  $cookies
     * @param array  $server
     * @param array  $files
     * @param string $body
     * @access public
     */
    public function __construct(
        array $params_get = array(),
        array $params_post = array(),
        array $cookies = array(),
        array $server = array(),
        array $files = array(),
        $body = null
    ) {
        // Assignment city...
        $this->params_get   = new DataCollection($params_get);
        $this->params_post  = new DataCollection($params_post);
        $this->cookies      = new DataCollection($cookies);
        $this->server       = new ServerDataCollection($server);
        $this->headers      = new HeaderDataCollection($this->server->getHeaders());
        $this->files        = new DataCollection($files);
        $this->body         = $body ? (string) $body : null;

        // Non-injected assignments
        $this->params_named = new DataCollection();
    }

    /**
     * Create a new request object using the built-in "superglobals"
     *
     * @link http://php.net/manual/en/language.variables.superglobals.php
     * @static
     * @access public
     * @return Request
     */
    public static function createFromGlobals()
    {
        // Create and return a new instance of this
        return new static(
            $_GET,
            $_POST,
            $_COOKIE,
            $_SERVER,
            $_FILES,
            null // Let our content getter take care of the "body"
        );
    }

    /**
     * Gets a unique ID for the request
     *
     * Generates one on the first call
     *
     * @param boolean $hash     Whether or not to hash the ID on creation
     * @access public
     * @return string
     */
    public function id($hash = true)
    {
        if (null === $this->id) {
            $this->id = uniqid();

            if ($hash) {
                $this->id = sha1($this->id);
            }
        }

        return $this->id;
    }

    /**
     * Returns the GET parameters collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function paramsGet()
    {
        return $this->params_get;
    }

    /**
     * Returns the POST parameters collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function paramsPost()
    {
        return $this->params_post;
    }

    /**
     * Returns the named parameters collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function paramsNamed()
    {
        return $this->params_named;
    }

    /**
     * Returns the cookies collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Returns the server collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * Returns the headers collection
     *
     * @access public
     * @return \Klein\DataCollection\HeaderDataCollection
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Returns the files collection
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function files()
    {
        return $this->files;
    }

    /**
     * Gets the request body
     *
     * @access public
     * @return string
     */
    public function body()
    {
        // Only get it once
        if (null === $this->body) {
            $this->body = @file_get_contents('php://input');
        }

        return $this->body;
    }

    /**
     * Returns all parameters (GET, POST, named, and cookies) that match the mask
     *
     * Takes an optional mask param that contains the names of any params
     * you'd like this method to exclude in the returned array
     *
     * @see \Klein\DataCollection\DataCollection::all()
     * @param array $mask               The parameter mask array
     * @param boolean $fill_with_nulls  Whether or not to fill the returned array
     *  with null values to match the given mask
     * @access public
     * @return array
     */
    public function params($mask = null, $fill_with_nulls = true)
    {
        /*
         * Make sure that each key in the mask has at least a
         * null value, since the user will expect the key to exist
         */
        if (null !== $mask && $fill_with_nulls) {
            $attributes = array_fill_keys($mask, null);
        } else {
            $attributes = array();
        }

        // Merge our params in the get, post, cookies, named order
        return array_merge(
            $attributes,
            $this->params_get->all($mask, false),
            $this->params_post->all($mask, false),
            $this->cookies->all($mask, false),
            $this->params_named->all($mask, false) // Add our named params last
        );
    }

    /**
     * Return a request parameter, or $default if it doesn't exist
     *
     * @param string $key       The name of the parameter to return
     * @param mixed $default    The default value of the parameter if it contains no value
     * @access public
     * @return string
     */
    public function param($key, $default = null)
    {
        // Get all of our request params
        $params = $this->params();

        return isset($params[$key]) ? $params[$key] : $default;
    }

    /**
     * Magic "__isset" method
     *
     * Allows the ability to arbitrarily check the existence of a parameter
     * from this instance while treating it as an instance property
     *
     * @param string $param     The name of the parameter
     * @access public
     * @return boolean
     */
    public function __isset($param)
    {
        // Get all of our request params
        $params = $this->params();

        return isset($params[$param]);
    }

    /**
     * Magic "__get" method
     *
     * Allows the ability to arbitrarily request a parameter from this instance
     * while treating it as an instance property
     *
     * @param string $param     The name of the parameter
     * @access public
     * @return string
     */
    public function __get($param)
    {
        return $this->param($param);
    }

    /**
     * Magic "__set" method
     *
     * Allows the ability to arbitrarily set a parameter from this instance
     * while treating it as an instance property
     *
     * NOTE: This currently sets the "named" parameters, since that's the
     * one collection that we have the most sane control over
     *
     * @param string $param     The name of the parameter
     * @param mixed $value      The value of the parameter
     * @access public
     * @return void
     */
    public function __set($param, $value)
    {
        $this->params_named->set($param, $value);
    }

    /**
     * Magic "__unset" method
     *
     * Allows the ability to arbitrarily remove a parameter from this instance
     * while treating it as an instance property
     *
     * @param string $param     The name of the parameter
     * @access public
     * @return void
     */
    public function __unset($param)
    {
        $this->params_named->remove($param);
    }

    /**
     * Is the request secure?
     *
     * @access public
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->server->get('HTTPS') == true);
    }

    /**
     * Gets the request IP address
     *
     * @access public
     * @return string
     */
    public function ip()
    {
        return $this->server->get('REMOTE_ADDR');
    }

    /**
     * Gets the request user agent
     *
     * @access public
     * @return string
     */
    public function userAgent()
    {
        return $this->headers->get('USER_AGENT');
    }

    /**
     * Gets the request URI
     *
     * @access public
     * @return string
     */
    public function uri()
    {
        return $this->server->get('REQUEST_URI', '/');
    }

    /**
     * Get the request's pathname
     *
     * @access public
     * @return string
     */
    public function pathname()
    {
        $uri = $this->uri();

        // Strip the query string from the URI
        $uri = strstr($uri, '?', true) ?: $uri;

        return $uri;
    }

    /**
     * Gets the request method, or checks it against $is
     *
     * <code>
     * // POST request example
     * $request->method() // returns 'POST'
     * $request->method('post') // returns true
     * $request->method('get') // returns false
     * </code>
     * 
     * @param string $is				The method to check the current request method against
     * @param boolean $allow_override	Whether or not to allow HTTP method overriding via header or params
     * @access public
     * @return string | boolean
     */
    public function method($is = null, $allow_override = true)
    {
        $method = $this->server->get('REQUEST_METHOD', 'GET');

        // Override
        if ($allow_override && $method === 'POST') {
            // For legacy servers, override the HTTP method with the X-HTTP-Method-Override header or _method parameter
            if ($this->server->exists('X_HTTP_METHOD_OVERRIDE')) {
                $method = $this->server->get('X_HTTP_METHOD_OVERRIDE', $method);
            } else {
                $method = $this->param('_method', $method);
            }

            $method = strtoupper($method);
        }

        // We're doing a check
        if (null !== $is) {
            return strcasecmp($method, $is) === 0;
        }

        return $method;
    }

    /**
     * Adds to or modifies the current query string
     *
     * @param string $key   The name of the query param
     * @param mixed $value  The value of the query param
     * @access public
     * @return string
     */
    public function query($key, $value = null)
    {
        $query = array();

        parse_str(
            $this->server()->get('QUERY_STRING'),
            $query
        );

        if (is_array($key)) {
            $query = array_merge($query, $key);
        } else {
            $query[$key] = $value;
        }

        $request_uri = $this->uri();

        if (strpos($request_uri, '?') !== false) {
            $request_uri = strstr($request_uri, '?', true);
        }

        return $request_uri . (!empty($query) ? '?' . http_build_query($query) : null);
    }
}


/* End of src/Klein/Request.php */

/* -------------------- */

/* Start of src/Klein/Response.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * Response 
 * 
 * @uses        AbstractResponse
 * @package     Klein
 */
class Response extends AbstractResponse
{

    /**
     * Methods
     */

    /**
     * Enable response chunking
     *
     * @link https://github.com/chriso/klein.php/wiki/Response-Chunking
     * @link http://bit.ly/hg3gHb
     * @param string $str   An optional string to send as a response "chunk"
     * @access public
     * @return Response
     */
    public function chunk($str = null)
    {
        parent::chunk();

        if (null !== $str) {
            printf("%x\r\n", strlen($str));
            echo "$str\r\n";
            flush();
        }

        return $this;
    }

    /**
     * Dump a variable
     *
     * @param mixed $obj    The variable to dump
     * @access public
     * @return Response
     */
    public function dump($obj)
    {
        if (is_array($obj) || is_object($obj)) {
            $obj = print_r($obj, true);
        }

        $this->append('<pre>' .  htmlentities($obj, ENT_QUOTES) . "</pre><br />\n");

        return $this;
    }

    /**
     * Sends a file
     *
     * @param string $path      The path of the file to send
     * @param string $filename  The file's name
     * @param string $mimetype  The MIME type of the file
     * @access public
     * @return Response
     */
    public function file($path, $filename = null, $mimetype = null)
    {
        $this->body('');
        $this->noCache();

        set_time_limit(1200);

        if (null === $filename) {
            $filename = basename($path);
        }
        if (null === $mimetype) {
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        }

        $this->header('Content-type', $mimetype);
        $this->header('Content-length', filesize($path));
        $this->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        $this->send();

        readfile($path);

        return $this;
    }

    /**
     * Sends an object as json or jsonp by providing the padding prefix
     *
     * @param mixed $object         The data to encode as JSON
     * @param string $jsonp_prefix  The name of the JSON-P function prefix
     * @access public
     * @return Response
     */
    public function json($object, $jsonp_prefix = null)
    {
        $this->body('');
        $this->noCache();

        set_time_limit(1200);

        $json = json_encode($object);

        if (null !== $jsonp_prefix) {
            // Should ideally be application/json-p once adopted
            $this->header('Content-Type', 'text/javascript');
            $this->body("$jsonp_prefix($json);");
        } else {
            $this->header('Content-Type', 'application/json');
            $this->body($json);
        }

        $this->send();

        return $this;
    }
}


/* End of src/Klein/Response.php */

/* -------------------- */

/* Start of src/Klein/Validator.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */







/**
 * Validator 
 * 
 * @package    Klein
 */
class Validator
{

    /**
     * Class properties
     */

    /**
     * The available validator methods
     *
     * @static
     * @var array
     * @access protected
     */
    public static $methods = array();

    /**
     * The string to validate
     *
     * @var string
     * @access protected
     */
    protected $str;

    /**
     * The custom exception message to throw on validation failure
     *
     * @var string
     * @access protected
     */
    protected $err;

    /**
     * Flag for whether the default validation methods have been added or not
     *
     * @static
     * @var boolean
     * @access protected
     */
    protected static $defaultAdded = false;


    /**
     * Methods
     */

    /**
     * Sets up the validator chain with the string and optional error message
     *
     * @param string $str   The string to validate
     * @param string $err   The optional custom exception message to throw on validation failure
     * @access public
     */
    public function __construct($str, $err = null)
    {
        $this->str = $str;
        $this->err = $err;

        if (!static::$defaultAdded) {
            static::addDefault();
        }
    }

    /**
     * Adds default validators on first use
     *
     * @static
     * @access public
     * @return void
     */
    public static function addDefault()
    {
        static::$methods['null'] = function ($str) {
            return $str === null || $str === '';
        };
        static::$methods['len'] = function ($str, $min, $max = null) {
            $len = strlen($str);
            return null === $max ? $len === $min : $len >= $min && $len <= $max;
        };
        static::$methods['int'] = function ($str) {
            return (string)$str === ((string)(int)$str);
        };
        static::$methods['float'] = function ($str) {
            return (string)$str === ((string)(float)$str);
        };
        static::$methods['email'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
        };
        static::$methods['url'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_URL) !== false;
        };
        static::$methods['ip'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_IP) !== false;
        };
        static::$methods['remoteip'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        };
        static::$methods['alnum'] = function ($str) {
            return ctype_alnum($str);
        };
        static::$methods['alpha'] = function ($str) {
            return ctype_alpha($str);
        };
        static::$methods['contains'] = function ($str, $needle) {
            return strpos($str, $needle) !== false;
        };
        static::$methods['regex'] = function ($str, $pattern) {
            return preg_match($pattern, $str);
        };
        static::$methods['chars'] = function ($str, $chars) {
            return preg_match("/^[$chars]++$/i", $str);
        };

        static::$defaultAdded = true;
    }

    /**
     * Add a custom validator to our list of validation methods
     *
     * @param string $method        The name of the validator method
     * @param callable $callback    The callback to perform on validation
     * @static
     * @access public
     * @return void
     */
    public static function addValidator($method, $callback)
    {
        static::$methods[strtolower($method)] = $callback;
    }

    /**
     * Magic "__call" method
     *
     * Allows the ability to arbitrarily call a validator with an optional prefix
     * of "is" or "not" by simply calling an instance property like a callback
     *
     * @param callable $method          The callable method to execute
     * @param array $args               The argument array to pass to our callback
     * @throws BadMethodCallException   If an attempt was made to call a validator modifier that doesn't exist
     * @throws ValidationException      If the validation check returns false
     * @access public
     * @return Validator
     */
    public function __call($method, $args)
    {
        $reverse = false;
        $validator = $method;
        $method_substr = substr($method, 0, 2);

        if ($method_substr === 'is') {       // is<$validator>()
            $validator = substr($method, 2);
        } elseif ($method_substr === 'no') { // not<$validator>()
            $validator = substr($method, 3);
            $reverse = true;
        }

        $validator = strtolower($validator);

        if (!$validator || !isset(static::$methods[$validator])) {
            throw new BadMethodCallException('Unknown method '. $method .'()');
        }

        $validator = static::$methods[$validator];
        array_unshift($args, $this->str);

        switch (count($args)) {
            case 1:
                $result = $validator($args[0]);
                break;
            case 2:
                $result = $validator($args[0], $args[1]);
                break;
            case 3:
                $result = $validator($args[0], $args[1], $args[2]);
                break;
            case 4:
                $result = $validator($args[0], $args[1], $args[2], $args[3]);
                break;
            default:
                $result = call_user_func_array($validator, $args);
                break;
        }

        $result = (bool)($result ^ $reverse);

        if (false === $this->err) {
            return $result;
        } elseif (false === $result) {
            throw new ValidationException($this->err);
        }

        return $this;
    }
}


/* End of src/Klein/Validator.php */

/* -------------------- */

/* Start of src/Klein/HttpStatus.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * HttpStatus 
 *
 * HTTP status code and message translator
 * 
 * @package     Klein
 */
class HttpStatus
{

    /**
     * The HTTP status code
     *
     * @var int
     * @access protected
     */
    protected $code;

    /**
     * The HTTP status message
     *
     * @var string
     * @access protected
     */
    protected $message;

    /**
     * HTTP 1.1 status messages based on code
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @static
     * @var array
     * @access protected
     */
    protected static $http_messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );


    /**
     * Constructor
     *
     * @param int $code The HTTP code
     * @param string $message (optional) HTTP message for the corresponding code
     * @access public
     * @return void
     */
    public function __construct($code, $message = null)
    {
        $this->setCode($code);

        if (null === $message) {
            $message = static::getMessageFromCode($code);
        }

        $this->message = $message;
    }

    /**
     * Get the HTTP status code
     *
     * @access public
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the HTTP status message
     *
     * @access public
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the HTTP status code
     *
     * @param int $code 
     * @access public
     * @return HttpStatus
     */
    public function setCode($code)
    {
        $this->code = (int) $code;
        return $this;
    }

    /**
     * Set the HTTP status message
     *
     * @param string $message 
     * @access public
     * @return HttpStatus
     */
    public function setMessage($message)
    {
        $this->message = (string) $message;
        return $this;
    }

    /**
     * Get a string representation of our HTTP status
     * 
     * @access public
     * @return string
     */
    public function getFormattedString()
    {
        $string = (string) $this->code;

        if (null !== $this->message) {
            $string = $string . ' ' . $this->message;
        }

        return $string;
    }

    /**
     * Magic "__toString" method
     *
     * Allows the ability to arbitrarily use an instance of this class as a string
     * This method will be automatically called, returning a string representation
     * of this instance
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->getFormattedString();
    }

    /**
     * Get our HTTP 1.1 message from our passed code
     *
     * Returns null if no corresponding message was
     * found for the passed in code
     *
     * @param int $int 
     * @static
     * @access public
     * @return string | null
     */
    public static function getMessageFromCode($int)
    {
        if (isset(static::$http_messages[ $int ])) {
            return static::$http_messages[ $int ];
        } else {
            return null;
        }
    }
}


/* End of src/Klein/HttpStatus.php */

/* -------------------- */

/* Start of src/Klein/RouteFactory.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * RouteFactory
 *
 * The default implementation of the AbstractRouteFactory
 *
 * @uses AbstractRouteFactory
 * @package     Klein
 */
class RouteFactory extends AbstractRouteFactory
{

    /**
     * Constants
     */

    /**
     * The value given to path's when they are entered as null values
     *
     * @const string
     */
    const NULL_PATH_VALUE = '*';


    /**
     * Methods
     */

    /**
     * Check if the path is null or equal to our match-all, null-like value
     *
     * @param mixed $path
     * @access protected
     * @return boolean
     */
    protected function pathIsNull($path)
    {
        return (static::NULL_PATH_VALUE === $path || null === $path);
    }

    /**
     * Quick check to see whether or not to count the route
     * as a match when counting total matches
     *
     * @param string $path
     * @access protected
     * @return boolean
     */
    protected function shouldPathStringCauseRouteMatch($path)
    {
        // Only consider a request to be matched when not using 'matchall'
        return !$this->pathIsNull($path);
    }

    /**
     * Pre-process a path string
     *
     * This method wraps the path string in a regular expression syntax baesd
     * on whether the string is a catch-all or custom regular expression.
     * It also adds the namespace in a specific part, based on the style of expression
     *
     * @param string $path
     * @access protected
     * @return string
     */
    protected function preprocessPathString($path)
    {
        // If the path is null, make sure to give it our match-all value
        $path = (null === $path) ? static::NULL_PATH_VALUE : (string) $path;

        // If a custom regular expression (or negated custom regex)
        if ($this->namespace && $path[0] === '@' || ($path[0] === '!' && $path[1] === '@')) {
            // Is it negated?
            if ($path[0] === '!') {
                $negate = true;
                $path = substr($path, 2);
            } else {
                $negate = false;
                $path = substr($path, 1);
            }

            // Regex anchored to front of string
            if ($path[0] === '^') {
                $path = substr($path, 1);
            } else {
                $path = '.*' . $path;
            }

            if ($negate) {
                $path = '@^' . $this->namespace . '(?!' . $path . ')';
            } else {
                $path = '@^' . $this->namespace . $path;
            }

        } elseif ($this->namespace && $this->pathIsNull($path)) {
            // Empty route with namespace is a match-all
            $path = '@^' . $this->namespace . '(/|$)';
        } else {
            // Just prepend our namespace
            $path = $this->namespace . $path;
        }

        return $path;
    }

    /**
     * Build a Route instance
     *
     * @param callable $callback    Callable callback method to execute on route match
     * @param string $path          Route URI path to match
     * @param string|array $method  HTTP Method to match
     * @param boolean $count_match  Whether or not to count the route as a match when counting total matches
     * @param string $name          The name of the route
     * @static
     * @access public
     * @return Route
     */
    public function build($callback, $path = null, $method = null, $count_match = true, $name = null)
    {
        return new Route(
            $callback,
            $this->preprocessPathString($path),
            $method,
            $this->shouldPathStringCauseRouteMatch($path) // Ignore the $count_match boolean that they passed
        );
    }
}


/* End of src/Klein/RouteFactory.php */

/* -------------------- */

/* Start of src/Klein/ResponseCookie.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * ResponseCookie
 *
 * Class to represent an HTTP response cookie
 *
 * @package     Klein
 */
class ResponseCookie
{

    /**
     * Class properties
     */

    /**
     * The name of the cookie
     *
     * @var string
     * @access protected
     */
    protected $name;

    /**
     * The string "value" of the cookie
     *
     * @var string
     * @access protected
     */
    protected $value;

    /**
     * The date/time that the cookie should expire
     *
     * Represented by a Unix "Timestamp"
     *
     * @var int
     * @access protected
     */
    protected $expire;

    /**
     * The path on the server that the cookie will
     * be available on
     *
     * @var string
     * @access protected
     */
    protected $path;

    /**
     * The domain that the cookie is available to
     *
     * @var string
     * @access protected
     */
    protected $domain;

    /**
     * Whether the cookie should only be transferred
     * over an HTTPS connection or not
     *
     * @var boolean
     * @access protected
     */
    protected $secure;

    /**
     * Whether the cookie will be available through HTTP
     * only (not available to be accessed through
     * client-side scripting languages like JavaScript)
     *
     * @var boolean
     * @access protected
     */
    protected $http_only;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param string  $name         The name of the cookie
     * @param string  $value        The value to set the cookie with
     * @param int     $expire       The time that the cookie should expire
     * @param string  $path         The path of which to restrict the cookie
     * @param string  $domain       The domain of which to restrict the cookie
     * @param boolean $secure       Flag of whether the cookie should only be sent over a HTTPS connection
     * @param boolean $http_only    Flag of whether the cookie should only be accessible over the HTTP protocol
     * @access public
     */
    public function __construct(
        $name,
        $value = null,
        $expire = null,
        $path = null,
        $domain = null,
        $secure = false,
        $http_only = false
    ) {
        // Initialize our properties
        $this->setName($name);
        $this->setValue($value);
        $this->setExpire($expire);
        $this->setPath($path);
        $this->setDomain($domain);
        $this->setSecure($secure);
        $this->setHttpOnly($http_only);
    }

    /**
     * Gets the cookie's name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the cookie's name
     *
     * @param string $name
     * @access public
     * @return ResponseCookie
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Gets the cookie's value
     *
     * @access public
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the cookie's value
     *
     * @param string $value
     * @access public
     * @return ResponseCookie
     */
    public function setValue($value)
    {
        if (null !== $value) {
            $this->value = (string) $value;
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * Gets the cookie's expire time
     *
     * @access public
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }
    
    /**
     * Sets the cookie's expire time
     *
     * The time should be an integer
     * representing a Unix timestamp
     *
     * @param int $expire
     * @access public
     * @return ResponseCookie
     */
    public function setExpire($expire)
    {
        if (null !== $expire) {
            $this->expire = (int) $expire;
        } else {
            $this->expire = $expire;
        }

        return $this;
    }

    /**
     * Gets the cookie's path
     *
     * @access public
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Sets the cookie's path
     *
     * @param string $path
     * @access public
     * @return ResponseCookie
     */
    public function setPath($path)
    {
        if (null !== $path) {
            $this->path = (string) $path;
        } else {
            $this->path = $path;
        }

        return $this;
    }

    /**
     * Gets the cookie's domain
     *
     * @access public
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }
    
    /**
     * Sets the cookie's domain
     *
     * @param string $domain
     * @access public
     * @return ResponseCookie
     */
    public function setDomain($domain)
    {
        if (null !== $domain) {
            $this->domain = (string) $domain;
        } else {
            $this->domain = $domain;
        }

        return $this;
    }

    /**
     * Gets the cookie's secure only flag
     *
     * @access public
     * @return boolean
     */
    public function getSecure()
    {
        return $this->secure;
    }
    
    /**
     * Sets the cookie's secure only flag
     *
     * @param boolean $secure
     * @access public
     * @return ResponseCookie
     */
    public function setSecure($secure)
    {
        $this->secure = (boolean) $secure;

        return $this;
    }

    /**
     * Gets the cookie's HTTP only flag
     *
     * @access public
     * @return boolean
     */
    public function getHttpOnly()
    {
        return $this->http_only;
    }
    
    /**
     * Sets the cookie's HTTP only flag
     *
     * @param boolean $http_only
     * @access public
     * @return ResponseCookie
     */
    public function setHttpOnly($http_only)
    {
        $this->http_only = (boolean) $http_only;

        return $this;
    }
}


/* End of src/Klein/ResponseCookie.php */

/* -------------------- */

/* Start of src/Klein/ServiceProvider.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * ServiceProvider 
 *
 * Service provider class for handling logic extending between
 * a request's data and a response's behavior
 * 
 * @package     Klein
 */
class ServiceProvider
{

    /**
     * Class properties
     */

    /**
     * The Request instance containing HTTP request data and behaviors
     *
     * @var Request
     * @access protected
     */
    protected $request;

    /**
     * The Response instance containing HTTP response data and behaviors
     *
     * @var Response
     * @access protected
     */
    protected $response;

    /**
     * The id of the current PHP session
     *
     * @var string
     * @access protected
     */
    protected $session_id;

    /**
     * The view layout
     *
     * @var string
     * @access protected
     */
    protected $layout;

    /**
     * The view to render
     *
     * @var string
     * @access protected
     */
    protected $view;

    /**
     * Shared data collection
     *
     * @var \Klein\DataCollection\DataCollection
     * @access protected
     */
    protected $shared_data;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param Request $request              Object containing all HTTP request data and behaviors
     * @param AbstractResponse $response    Object containing all HTTP response data and behaviors
     * @access public
     */
    public function __construct(Request $request = null, AbstractResponse $response = null)
    {
        // Bind our objects
        $this->bind($request, $response);

        // Instantiate our shared data collection
        $this->shared_data = new DataCollection();
    }

    /**
     * Bind object instances to this service
     *
     * @param Request $request              Object containing all HTTP request data and behaviors
     * @param AbstractResponse $response    Object containing all HTTP response data and behaviors
     * @access public
     * @return ServiceProvider
     */
    public function bind(Request $request = null, AbstractResponse $response = null)
    {
        // Keep references
        $this->request  = $request  ?: $this->request;
        $this->response = $response ?: $this->response;

        return $this;
    }

    /**
     * Returns the shared data collection object
     *
     * @access public
     * @return \Klein\DataCollection\DataCollection
     */
    public function sharedData()
    {
        return $this->shared_data;
    }

    /**
     * Get the current session's ID
     *
     * This will start a session if the current session id is null
     *
     * @access public
     * @return string|false
     */
    public function startSession()
    {
        if (session_id() === '') {
            // Attempt to start a session
            session_start();

            $this->session_id = session_id() ?: false;
        }

        return $this->session_id;
    }

    /**
     * Stores a flash message of $type
     *
     * @param string $msg       The message to flash
     * @param string $type      The flash message type
     * @param array $params     Optional params to be parsed by markdown
     * @access public
     * @return void
     */
    public function flash($msg, $type = 'info', $params = null)
    {
        $this->startSession();
        if (is_array($type)) {
            $params = $type;
            $type = 'info';
        }
        if (!isset($_SESSION['__flashes'])) {
            $_SESSION['__flashes'] = array($type => array());
        } elseif (!isset($_SESSION['__flashes'][$type])) {
            $_SESSION['__flashes'][$type] = array();
        }
        $_SESSION['__flashes'][$type][] = $this->markdown($msg, $params);
    }

    /**
     * Returns and clears all flashes of optional $type
     *
     * @param string $type  The name of the flash message type
     * @access public
     * @return array
     */
    public function flashes($type = null)
    {
        $this->startSession();
        if (!isset($_SESSION['__flashes'])) {
            return array();
        }
        if (null === $type) {
            $flashes = $_SESSION['__flashes'];
            unset($_SESSION['__flashes']);
        } elseif (null !== $type) {
            $flashes = array();
            if (isset($_SESSION['__flashes'][$type])) {
                $flashes = $_SESSION['__flashes'][$type];
                unset($_SESSION['__flashes'][$type]);
            }
        }
        return $flashes;
    }

    /**
     * Render a text string as markdown
     *
     * Supports basic markdown syntax
     *
     * Also, this method takes in EITHER an array of optional arguments (as the second parameter)
     * ... OR this method will simply take a variable number of arguments (after the initial str arg)
     *
     * @param string $str   The text string to parse
     * @param array $args   Optional arguments to be parsed by markdown
     * @static
     * @access public
     * @return string
     */
    public static function markdown($str, $args = null)
    {
        // Create our markdown parse/conversion regex's
        $md = array(
            '/\[([^\]]++)\]\(([^\)]++)\)/' => '<a href="$2">$1</a>',
            '/\*\*([^\*]++)\*\*/'          => '<strong>$1</strong>',
            '/\*([^\*]++)\*/'              => '<em>$1</em>'
        );

        // Let's make our arguments more "magical"
        $args = func_get_args(); // Grab all of our passed args
        $str = array_shift($args); // Remove the initial arg from the array (and set the $str to it)
        if (isset($args[0]) && is_array($args[0])) {
            /**
             * If our "second" argument (now the first array item is an array)
             * just use the array as the arguments and forget the rest
             */
            $args = $args[0];
        }

        // Encode our args so we can insert them into an HTML string
        foreach ($args as &$arg) {
            $arg = htmlentities($arg, ENT_QUOTES, 'UTF-8');
        }

        // Actually do our markdown conversion
        return vsprintf(preg_replace(array_keys($md), $md, $str), $args);
    }

    /**
     * Escapes a string
     *
     * @param string $str   The string to escape
     * @static
     * @access public
     * @return void
     */
    public static function escape($str)
    {
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Redirects the request to the current URL
     *
     * @access public
     * @return ServiceProvider
     */
    public function refresh()
    {
        $this->response->redirect(
            $this->request->uri()
        );

        return $this;
    }

    /**
     * Redirects the request back to the referrer
     *
     * @access public
     * @return ServiceProvider
     */
    public function back()
    {
        $referer = $this->request->server()->get('HTTP_REFERER');

        if (null !== $referer) {
            return $this->response->redirect($referer);
        }

        $this->refresh();

        return $this;
    }

    /**
     * Get (or set) the view's layout
     *
     * Simply calling this method without any arguments returns the current layout.
     * Calling with an argument, however, sets the layout to what was provided by the argument.
     *
     * @param string $layout    The layout of the view
     * @access public
     * @return string|ServiceProvider
     */
    public function layout($layout = null)
    {
        if (null !== $layout) {
            $this->layout = $layout;

            return $this;
        }

        return $this->layout;
    }

    /**
     * Renders the current view
     *
     * @access public
     * @return void
     */
    public function yieldView()
    {
        require $this->view;
    }

    /**
     * Renders a view + optional layout
     *
     * @param string $view  The view to render
     * @param array $data   The data to render in the view
     * @access public
     * @return void
     */
    public function render($view, array $data = array())
    {
        $original_view = $this->view;

        if (!empty($data)) {
            $this->shared_data->merge($data);
        }

        $this->view = $view;

        if (null === $this->layout) {
            $this->yieldView();
        } else {
            require $this->layout;
        }

        if (false !== $this->response->chunked) {
            $this->response->chunk();
        }

        // restore state for parent render()
        $this->view = $original_view;
    }

    /**
     * Renders a view without a layout
     *
     * @param string $view  The view to render
     * @param array $data   The data to render in the view
     * @access public
     * @return void
     */
    public function partial($view, array $data = array())
    {
        $layout = $this->layout;
        $this->layout = null;
        $this->render($view, $data);
        $this->layout = $layout;
    }

    /**
     * Add a custom validator for our validation method
     *
     * @param string $method        The name of the validator method
     * @param callable $callback    The callback to perform on validation
     * @access public
     * @return void
     */
    public function addValidator($method, $callback)
    {
        Validator::addValidator($method, $callback);
    }

    /**
     * Start a validator chain for the specified string
     *
     * @param string $string    The string to validate
     * @param string $err       The custom exception message to throw
     * @access public
     * @return Validator
     */
    public function validate($string, $err = null)
    {
        return new Validator($string, $err);
    }

    /**
     * Start a validator chain for the specified parameter
     *
     * @param string $param     The name of the parameter to validate
     * @param string $err       The custom exception message to throw
     * @access public
     * @return Validator
     */
    public function validateParam($param, $err = null)
    {
        return $this->validate($this->request->param($param), $err);
    }


    /**
     * Magic "__isset" method
     *
     * Allows the ability to arbitrarily check the existence of shared data
     * from this instance while treating it as an instance property
     *
     * @param string $key     The name of the shared data
     * @access public
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->shared_data->exists($key);
    }

    /**
     * Magic "__get" method
     *
     * Allows the ability to arbitrarily request shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key     The name of the shared data
     * @access public
     * @return string
     */
    public function __get($key)
    {
        return $this->shared_data->get($key);
    }

    /**
     * Magic "__set" method
     *
     * Allows the ability to arbitrarily set shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key     The name of the shared data
     * @param mixed $value      The value of the shared data
     * @access public
     * @return void
     */
    public function __set($key, $value)
    {
        $this->shared_data->set($key, $value);
    }

    /**
     * Magic "__unset" method
     *
     * Allows the ability to arbitrarily remove shared data from this instance
     * while treating it as an instance property
     *
     * @param string $key     The name of the shared data
     * @access public
     * @return void
     */
    public function __unset($key)
    {
        $this->shared_data->remove($key);
    }
}


/* End of src/Klein/ServiceProvider.php */

/* -------------------- */

/* Start of src/Klein/AbstractResponse.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */









/**
 * AbstractResponse
 * 
 * @abstract
 * @package     Klein
 */
abstract class AbstractResponse
{

    /**
     * Properties
     */

    /**
     * The default response HTTP status code
     *
     * @static
     * @var int
     * @access protected
     */
    protected static $default_status_code = 200;

    /**
     * The HTTP version of the response
     *
     * @var string
     * @access protected
     */
    protected $protocol_version = '1.1';

    /**
     * The response body
     *
     * @var string
     * @access protected
     */
    protected $body;

    /**
     * HTTP response status
     *
     * @var \Klein\HttpStatus
     * @access protected
     */
    protected $status;

    /**
     * HTTP response headers
     *
     * @var \Klein\DataCollection\HeaderDataCollection
     * @access protected
     */
    protected $headers;

    /**
     * HTTP response cookies
     *
     * @var \Klein\DataCollection\ResponseCookieDataCollection
     * @access protected
     */
    protected $cookies;

    /**
     * Whether or not the response is "locked" from
     * any further modification
     *
     * @var boolean
     * @access protected
     */
    protected $locked = false;

    /**
     * Whether or not the response has been sent
     *
     * @var boolean
     * @access protected
     */
    protected $sent = false;

    /**
     * Whether the response has been chunked or not
     *
     * @var boolean
     * @access public
     */
    public $chunked = false;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * Create a new AbstractResponse object with a dependency injected Headers instance
     *
     * @param string $body          The response body's content
     * @param int $status_code      The status code
     * @param array $headers        The response header "hash"
     * @access public
     */
    public function __construct($body = '', $status_code = null, array $headers = array())
    {
        $status_code   = $status_code ?: static::$default_status_code;

        // Set our body and code using our internal methods
        $this->body($body);
        $this->code($status_code);

        $this->headers = new HeaderDataCollection($headers);
        $this->cookies = new ResponseCookieDataCollection();
    }

    /**
     * Get (or set) the HTTP protocol version
     *
     * Simply calling this method without any arguments returns the current protocol version.
     * Calling with an integer argument, however, attempts to set the protocol version to what
     * was provided by the argument.
     *
     * @param string $protocol_version
     * @access public
     * @return string|AbstractResponse
     */
    public function protocolVersion($protocol_version = null)
    {
        if (null !== $protocol_version) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->protocol_version = (string) $protocol_version;

            return $this;
        }

        return $this->protocol_version;
    }

    /**
     * Get (or set) the response's body content
     *
     * Simply calling this method without any arguments returns the current response body.
     * Calling with an argument, however, sets the response body to what was provided by the argument.
     *
     * @param string $body  The body content string
     * @access public
     * @return string|AbstractResponse
     */
    public function body($body = null)
    {
        if (null !== $body) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->body = (string) $body;

            return $this;
        }

        return $this->body;
    }

    /**
     * Returns the status object
     *
     * @access public
     * @return \Klein\HttpStatus
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Returns the headers collection
     *
     * @access public
     * @return \Klein\DataCollection\HeaderDataCollection
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Returns the cookies collection
     *
     * @access public
     * @return \Klein\DataCollection\ResponseCookieDataCollection
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Get (or set) the HTTP response code
     *
     * Simply calling this method without any arguments returns the current response code.
     * Calling with an integer argument, however, attempts to set the response code to what
     * was provided by the argument.
     *
     * @param int $code     The HTTP status code to send
     * @access public
     * @return int|AbstractResponse
     */
    public function code($code = null)
    {
        if (null !== $code) {
            // Require that the response be unlocked before changing it
            $this->requireUnlocked();

            $this->status = new HttpStatus($code);

            return $this;
        }

        return $this->status->getCode();
    }

    /**
     * Prepend a string to the response's content body
     *
     * @param string $content   The string to prepend
     * @access public
     * @return AbstractResponse
     */
    public function prepend($content)
    {
        // Require that the response be unlocked before changing it
        $this->requireUnlocked();

        $this->body = $content . $this->body;

        return $this;
    }

    /**
     * Append a string to the response's content body
     *
     * @param string $content   The string to append
     * @access public
     * @return AbstractResponse
     */
    public function append($content)
    {
        // Require that the response be unlocked before changing it
        $this->requireUnlocked();

        $this->body .= $content;

        return $this;
    }

    /**
     * Check if the response is locked
     *
     * @access public
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * Require that the response is unlocked
     *
     * Throws an exception if the response is locked,
     * preventing any methods from mutating the response
     * when its locked
     *
     * @throws LockedResponseException  If the response is locked
     * @access public
     * @return AbstractResponse
     */
    public function requireUnlocked()
    {
        if ($this->isLocked()) {
            throw new LockedResponseException('Response is locked');
        }

        return $this;
    }

    /**
     * Lock the response from further modification
     *
     * @access public
     * @return AbstractResponse
     */
    public function lock()
    {
        $this->locked = true;

        return $this;
    }

    /**
     * Unlock the response from further modification
     *
     * @access public
     * @return AbstractResponse
     */
    public function unlock()
    {
        $this->locked = false;

        return $this;
    }

    /**
     * Generates an HTTP compatible status header line string
     *
     * Creates the string based off of the response's properties
     *
     * @access protected
     * @return string
     */
    protected function httpStatusLine()
    {
        return sprintf('HTTP/%s %s', $this->protocol_version, $this->status);
    }

    /**
     * Send our HTTP headers
     *
     * @param boolean $cookies_also Whether or not to also send the cookies after sending the normal headers
     * @param boolean $override     Whether or not to override the check if headers have already been sent
     * @access public
     * @return AbstractResponse
     */
    public function sendHeaders($cookies_also = true, $override = false)
    {
        if (headers_sent() && !$override) {
            return $this;
        }

        // Send our HTTP status line
        header($this->httpStatusLine());

        // Iterate through our Headers data collection and send each header
        foreach ($this->headers as $key => $value) {
            header($key .': '. $value, false);
        }

        if ($cookies_also) {
            $this->sendCookies($override);
        }

        return $this;
    }

    /**
     * Send our HTTP response cookies
     *
     * @param boolean $override     Whether or not to override the check if headers have already been sent
     * @access public
     * @return AbstractResponse
     */
    public function sendCookies($override = false)
    {
        if (headers_sent() && !$override) {
            return $this;
        }

        // Iterate through our Cookies data collection and set each cookie natively
        foreach ($this->cookies as $cookie) {
            // Use the built-in PHP "setcookie" function
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpire(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->getSecure(),
                $cookie->getHttpOnly()
            );
        }

        return $this;
    }

    /**
     * Send our body's contents
     *
     * @access public
     * @return AbstractResponse
     */
    public function sendBody()
    {
        echo (string) $this->body;

        return $this;
    }

    /**
     * Send the response and lock it
     *
     * @param boolean $override             Whether or not to override the check if the response has already been sent
     * @throws ResponseAlreadySentException If the response has already been sent
     * @access public
     * @return AbstractResponse
     */
    public function send($override = false)
    {
        if ($this->sent && !$override) {
            throw new ResponseAlreadySentException('Response has already been sent');
        }

        // Send our response data
        $this->sendHeaders();
        $this->sendBody();

        // Lock the response from further modification
        $this->lock();

        // Mark as sent
        $this->sent = true;

        // If there running FPM, tell the process manager to finish the server request/response handling
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * Check if the response has been sent
     *
     * @access public
     * @return boolean
     */
    public function isSent()
    {
        return $this->sent;
    }

    /**
     * Enable response chunking
     *
     * @link https://github.com/chriso/klein.php/wiki/Response-Chunking
     * @link http://bit.ly/hg3gHb
     * @access public
     * @return AbstractResponse
     */
    public function chunk()
    {
        if (false === $this->chunked) {
            $this->chunked = true;
            $this->header('Transfer-encoding', 'chunked');
            flush();
        }

        if (($body_length = strlen($this->body)) > 0) {
            printf("%x\r\n", $body_length);
            $this->sendBody();
            $this->body('');
            echo "\r\n";
            flush();
        }

        return $this;
    }

    /**
     * Sets a response header
     *
     * @param string $key       The name of the HTTP response header
     * @param mixed $value      The value to set the header with
     * @access public
     * @return AbstractResponse
     */
    public function header($key, $value)
    {
        $this->headers->set($key, $value);

        return $this;
    }

    /**
     * Sets a response cookie
     *
     * @param string $key           The name of the cookie
     * @param string $value         The value to set the cookie with
     * @param int $expiry           The time that the cookie should expire
     * @param string $path          The path of which to restrict the cookie
     * @param string $domain        The domain of which to restrict the cookie
     * @param boolean $secure       Flag of whether the cookie should only be sent over a HTTPS connection
     * @param boolean $httponly     Flag of whether the cookie should only be accessible over the HTTP protocol
     * @access public
     * @return AbstractResponse
     */
    public function cookie(
        $key,
        $value = '',
        $expiry = null,
        $path = '/',
        $domain = null,
        $secure = false,
        $httponly = false
    ) {
        if (null === $expiry) {
            $expiry = time() + (3600 * 24 * 30);
        }

        $this->cookies->set(
            $key,
            new ResponseCookie($key, $value, $expiry, $path, $domain, $secure, $httponly)
        );

        return $this;
    }

    /**
     * Tell the browser not to cache the response
     *
     * @access public
     * @return AbstractResponse
     */
    public function noCache()
    {
        $this->header('Pragma', 'no-cache');
        $this->header('Cache-Control', 'no-store, no-cache');

        return $this;
    }

    /**
     * Redirects the request to another URL
     *
     * @param string $url   The URL to redirect to
     * @param int $code     The HTTP status code to use for redirection
     * @access public
     * @return AbstractResponse
     */
    public function redirect($url, $code = 302)
    {
        $this->code($code);
        $this->header('Location', $url);
        $this->lock();

        return $this;
    }
}


/* End of src/Klein/AbstractResponse.php */

/* -------------------- */

/* Start of src/Klein/AbstractRouteFactory.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * AbstractRouteFactory
 *
 * Abstract class for a factory for building new Route instances
 *
 * @abstract
 * @package     Klein
 */
abstract class AbstractRouteFactory
{

    /**
     * Properties
     */

    /**
     * The namespace of which to collect the routes in
     * when matching, so you can define routes under a
     * common endpoint
     *
     * @var string
     * @access protected
     */
    protected $namespace;


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param string $namespace The initial namespace to set
     * @access public
     */
    public function __construct($namespace = null)
    {
        $this->namespace = $namespace;
    }

    /**
     * Gets the value of namespace
     *
     * @access public
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Sets the value of namespace
     *
     * @param string $namespace The namespace from which to collect the Routes under
     * @access public
     * @return AbstractRouteFactory
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string) $namespace;

        return $this;
    }

    /**
     * Append a namespace to the current namespace
     *
     * @param string $namespace The namespace from which to collect the Routes under
     * @access public
     * @return AbstractRouteFactory
     */
    public function appendNamespace($namespace)
    {
        $this->namespace .= (string) $namespace;

        return $this;
    }

    /**
     * Build factory method
     *
     * This method should be implemented to return a Route instance
     *
     * @param callable $callback    Callable callback method to execute on route match
     * @param string $path          Route URI path to match
     * @param string|array $method  HTTP Method to match
     * @param boolean $count_match  Whether or not to count the route as a match when counting total matches
     * @param string $name          The name of the route
     * @abstract
     * @access public
     * @return Klein\Route
     */
    abstract public function build($callback, $path = null, $method = null, $count_match = true, $name = null);
}


/* End of src/Klein/AbstractRouteFactory.php */

/* -------------------- */

} /* end of namespace Klein */

namespace Klein\DataCollection {
use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Klein\ResponseCookie;
use Klein\Route;

/* Start of src/Klein/DataCollection/DataCollection.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */








/**
 * DataCollection
 *
 * A generic collection class to contain array-like data, specifically
 * designed to work with HTTP data (request params, session data, etc)
 *
 * Inspired by @fabpot's Symfony 2's HttpFoundation
 * @link https://github.com/symfony/HttpFoundation/blob/master/ParameterBag.php
 *
 * @uses        IteratorAggregate
 * @uses        ArrayAccess
 * @uses        Countable
 * @package     Klein\DataCollection
 */
class DataCollection implements IteratorAggregate, ArrayAccess, Countable
{

    /**
     * Class properties
     */

    /**
     * Collection of data attributes
     *
     * @var array
     * @access protected
     */
    protected $attributes = array();


    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @param array $attributes The data attributes of this collection
     * @access public
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes = $attributes;
    }

    /**
     * Returns all of the key names in the collection
     *
     * If an optional mask array is passed, this only
     * returns the keys that match the mask
     *
     * @param array $mask               The parameter mask array
     * @param boolean $fill_with_nulls  Whether or not to fill the returned array with
     *  values to match the given mask, even if they don't exist in the collection
     * @access public
     * @return array
     */
    public function keys($mask = null, $fill_with_nulls = true)
    {
        if (null !== $mask) {
            // Support a more "magical" call
            if (!is_array($mask)) {
                $mask = func_get_args();
            }

            /*
             * Make sure that the returned array has at least the values
             * passed into the mask, since the user will expect them to exist
             */
            if ($fill_with_nulls) {
                $keys = $mask;
            } else {
                $keys = array();
            }

            /*
             * Remove all of the values from the keys
             * that aren't in the passed mask
             */
            return array_intersect(
                array_keys($this->attributes),
                $mask
            ) + $keys;
        }

        return array_keys($this->attributes);
    }

    /**
     * Returns all of the attributes in the collection
     *
     * If an optional mask array is passed, this only
     * returns the keys that match the mask
     *
     * @param array $mask               The parameter mask array
     * @param boolean $fill_with_nulls  Whether or not to fill the returned array with
     *  values to match the given mask, even if they don't exist in the collection
     * @access public
     * @return array
     */
    public function all($mask = null, $fill_with_nulls = true)
    {
        if (null !== $mask) {
            // Support a more "magical" call
            if (!is_array($mask)) {
                $mask = func_get_args();
            }

            /*
             * Make sure that each key in the mask has at least a
             * null value, since the user will expect the key to exist
             */
            if ($fill_with_nulls) {
                $attributes = array_fill_keys($mask, null);
            } else {
                $attributes = array();
            }

            /*
             * Remove all of the keys from the attributes
             * that aren't in the passed mask
             */
            return array_intersect_key(
                $this->attributes,
                array_flip($mask)
            ) + $attributes;
        }

        return $this->attributes;
    }

    /**
     * Return an attribute of the collection
     *
     * Return a default value if the key doesn't exist
     *
     * @param string $key           The name of the parameter to return
     * @param mixed  $default_val   The default value of the parameter if it contains no value
     * @access public
     * @return mixed
     */
    public function get($key, $default_val = null)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $default_val;
    }

    /**
     * Set an attribute of the collection
     *
     * @param string $key   The name of the parameter to set
     * @param mixed  $value The value of the parameter to set
     * @access public
     * @return DataCollection
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Replace the collection's attributes
     *
     * @param array $attributes The attributes to replace the collection's with
     * @access public
     * @return DataCollection
     */
    public function replace(array $attributes = array())
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Merge attributes with the collection's attributes
     *
     * Optionally allows a second boolean parameter to merge the attributes
     * into the collection in a "hard" manner, using the "array_replace"
     * method instead of the usual "array_merge" method
     *
     * @param array $attributes The attributes to merge into the collection
     * @param boolean $hard     Whether or not to make the merge "hard"
     * @access public
     * @return DataCollection
     */
    public function merge(array $attributes = array(), $hard = false)
    {
        // Don't waste our time with an "array_merge" call if the array is empty
        if (!empty($attributes)) {
            // Hard merge?
            if ($hard) {
                $this->attributes = array_replace(
                    $this->attributes,
                    $attributes
                );
            } else {
                $this->attributes = array_merge(
                    $this->attributes,
                    $attributes
                );
            }
        }

        return $this;
    }

    /**
     * See if an attribute exists in the collection
     *
     * @param string $key   The name of the parameter
     * @access public
     * @return boolean
     */
    public function exists($key)
    {
        // Don't use "isset", since it returns false for null values
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Remove an attribute from the collection
     *
     * @param string $key   The name of the parameter
     * @access public
     * @return void
     */
    public function remove($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Clear the collection's contents
     *
     * Semantic alias of a no-argument `$this->replace` call
     *
     * @access public
     * @return DataCollection
     */
    public function clear()
    {
        return $this->replace();
    }

    /**
     * Check if the collection is empty
     *
     * @access public
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->attributes);
    }

    /**
     * A quick convenience method to get an empty clone of the
     * collection. Great for dependency injection. :)
     *
     * @access public
     * @return DataCollection
     */
    public function cloneEmpty()
    {
        $clone = clone $this;
        $clone->clear();

        return $clone;
    }


    /*
     * Magic method implementations
     */

    /**
     * Magic "__get" method
     *
     * Allows the ability to arbitrarily request an attribute from
     * this instance while treating it as an instance property
     *
     * @see get()
     * @param string $key   The name of the parameter to return
     * @access public
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic "__set" method
     *
     * Allows the ability to arbitrarily set an attribute from
     * this instance while treating it as an instance property
     *
     * @see set()
     * @param string $key   The name of the parameter to set
     * @param mixed  $value The value of the parameter to set
     * @access public
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic "__isset" method
     *
     * Allows the ability to arbitrarily check the existence of an attribute
     * from this instance while treating it as an instance property
     *
     * @see exists()
     * @param string $key   The name of the parameter
     * @access public
     * @return boolean
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Magic "__unset" method
     *
     * Allows the ability to arbitrarily remove an attribute from
     * this instance while treating it as an instance property
     *
     * @see remove()
     * @param string $key   The name of the parameter
     * @access public
     * @return void
     */
    public function __unset($key)
    {
        $this->remove($key);
    }


    /*
     * Interface required method implementations
     */

    /**
     * Get the aggregate iterator
     *
     * IteratorAggregate interface required method
     *
     * @see \IteratorAggregate::getIterator()
     * @access public
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Get an attribute via array syntax
     *
     * Allows the access of attributes of this instance while treating it like an array
     *
     * @see \ArrayAccess::offsetGet()
     * @see get()
     * @param string $key   The name of the parameter to return
     * @access public
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set an attribute via array syntax
     *
     * Allows the access of attributes of this instance while treating it like an array
     *
     * @see \ArrayAccess::offsetSet()
     * @see set()
     * @param string $key   The name of the parameter to set
     * @param mixed  $value The value of the parameter to set
     * @access public
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Check existence an attribute via array syntax
     *
     * Allows the access of attributes of this instance while treating it like an array
     *
     * @see \ArrayAccess::offsetExists()
     * @see exists()
     * @param string $key   The name of the parameter
     * @access public
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * Remove an attribute via array syntax
     *
     * Allows the access of attributes of this instance while treating it like an array
     *
     * @see \ArrayAccess::offsetUnset()
     * @see remove()
     * @param string $key   The name of the parameter
     * @access public
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * Count the attributes via a simple "count" call
     *
     * Allows the use of the "count" function (or any internal counters)
     * to simply count the number of attributes in the collection.
     *
     * @see \Countable::count()
     * @access public
     * @return int
     */
    public function count()
    {
        return count($this->attributes);
    }
}


/* End of src/Klein/DataCollection/DataCollection.php */

/* -------------------- */

/* Start of src/Klein/DataCollection/RouteCollection.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * RouteCollection
 *
 * A DataCollection for Routes
 *
 * @uses        DataCollection
 * @package     Klein\DataCollection
 */
class RouteCollection extends DataCollection
{

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @override (doesn't call our parent)
     * @param array $routes The routes of this collection
     * @access public
     */
    public function __construct(array $routes = array())
    {
        foreach ($routes as $value) {
            $this->add($value);
        }
    }

    /**
     * Set a route
     *
     * {@inheritdoc}
     *
     * A value may either be a callable or a Route instance
     * Callable values will be converted into a Route with
     * the "name" of the route being set from the "key"
     *
     * A developer may add a named route to the collection
     * by passing the name of the route as the "$key" and an
     * instance of a Route as the "$value"
     *
     * @see DataCollection::set()
     * @param string $key                   The name of the route to set
     * @param Route|callable $value         The value of the route to set
     * @access public
     * @return RouteCollection
     */
    public function set($key, $value)
    {
        if (!$value instanceof Route) {
            $value = new Route($value);
        }

        return parent::set($key, $value);
    }

    /**
     * Add a route instance to the collection
     *
     * This will auto-generate a name
     *
     * @param Route $route
     * @access public
     * @return RouteCollection
     */
    public function addRoute(Route $route)
    {
        /**
         * Auto-generate a name from the object's hash
         * This makes it so that we can autogenerate names
         * that ensure duplicate route instances are overridden
         */
        $name = spl_object_hash($route);

        return $this->set($name, $route);
    }

    /**
     * Add a route to the collection
     *
     * This allows a more generic form that
     * will take a Route instance, string callable
     * or any other Route class compatible callback
     *
     * @param mixed $route
     * @access public
     * @return RouteCollection
     */
    public function add($route)
    {
        if (!$route instanceof Route) {
            $route = new Route($route);
        }

        return $this->addRoute($route);
    }

    /**
     * Prepare the named routes in the collection
     *
     * This loops through every route to set the collection's
     * key name for that route to equal the routes name, if
     * its changed
     *
     * @access public
     * @return RouteCollection
     */
    public function prepareNamed()
    {
        foreach ($this as $key => $route) {
            $route_name = $route->getName();

            if (null !== $route_name) {
                // Remove the route from the collection
                $this->remove($key);

                // Add the route back to the set with the new name
                $this->set($route_name, $route);
            }
        }

        return $this;
    }
}


/* End of src/Klein/DataCollection/RouteCollection.php */

/* -------------------- */

/* Start of src/Klein/DataCollection/HeaderDataCollection.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * HeaderDataCollection
 *
 * A DataCollection for HTTP headers
 *
 * @uses        DataCollection
 * @package     Klein\DataCollection
 */
class HeaderDataCollection extends DataCollection
{

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @override (doesn't call our parent)
     * @param array $headers The headers of this collection
     * @access public
     */
    public function __construct(array $headers = array())
    {
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get a header
     *
     * {@inheritdoc}
     *
     * @see DataCollection::get()
     * @param string $key           The name of the header to return
     * @param mixed  $default_val   The default value of the header if it contains no value
     * @access public
     * @return mixed
     */
    public function get($key, $default_val = null)
    {
        $key = static::normalizeName($key);

        return parent::get($key, $default_val);
    }

    /**
     * Set a header
     *
     * {@inheritdoc}
     *
     * @see DataCollection::set()
     * @param string $key   The name of the header to set
     * @param mixed  $value The value of the header to set
     * @access public
     * @return HeaderDataCollection
     */
    public function set($key, $value)
    {
        $key = static::normalizeName($key);

        return parent::set($key, $value);
    }

    /**
     * Check if a header exists
     *
     * {@inheritdoc}
     *
     * @see DataCollection::exists()
     * @param string $key   The name of the header
     * @access public
     * @return boolean
     */
    public function exists($key)
    {
        $key = static::normalizeName($key);

        return parent::exists($key);
    }

    /**
     * Remove a header
     *
     * {@inheritdoc}
     *
     * @see DataCollection::remove()
     * @param string $key   The name of the header
     * @access public
     * @return void
     */
    public function remove($key)
    {
        $key = static::normalizeName($key);

        parent::remove($key);
    }

    /**
     * Normalize a header name by formatting it in a standard way
     *
     * This is useful since PHP automatically capitalizes and underscore
     * separates the words of headers
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
     * @param string $name              The name ("field") of the header
     * @param boolean $make_lowercase   Whether or not to lowercase the name
     * @static
     * @access public
     * @return string
     */
    public static function normalizeName($name, $make_lowercase = true)
    {
        /**
         * Lowercasing header names allows for a more uniform appearance,
         * however header names are case-insensitive by specification
         */
        if ($make_lowercase) {
            $name = strtolower($name);
        }

        // Do some formatting and return
        return str_replace(
            array(' ', '_'),
            '-',
            trim($name)
        );
    }
}


/* End of src/Klein/DataCollection/HeaderDataCollection.php */

/* -------------------- */

/* Start of src/Klein/DataCollection/ServerDataCollection.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * ServerDataCollection
 *
 * A DataCollection for "$_SERVER" like data
 *
 * Look familiar?
 *
 * Inspired by @fabpot's Symfony 2's HttpFoundation
 * @link https://github.com/symfony/HttpFoundation/blob/master/ServerBag.php
 *
 * @uses DataCollection
 * @package     Klein\DataCollection
 */
class ServerDataCollection extends DataCollection
{

    /**
     * Class properties
     */

    /**
     * The prefix of HTTP headers normally
     * stored in the Server data
     *
     * @static
     * @var string
     * @access protected
     */
    protected static $http_header_prefix = 'HTTP_';

    /**
     * The list of HTTP headers that for some
     * reason aren't prefixed in PHP...
     *
     * @static
     * @var string
     * @access protected
     */
    protected static $http_nonprefixed_headers = array(
        'CONTENT_LENGTH',
        'CONTENT_TYPE',
        'CONTENT_MD5',
    );


    /**
     * Methods
     */

    /**
     * Quickly check if a string has a passed prefix
     *
     * @param string $string    The string to check
     * @param string $prefix    The prefix to test
     * @static
     * @access public
     * @return boolean
     */
    public static function hasPrefix($string, $prefix)
    {
        if (strpos($string, $prefix) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get our headers from our server data collection
     *
     * PHP is weird... it puts all of the HTTP request
     * headers in the $_SERVER array. This handles that
     *
     * @access public
     * @return array
     */
    public function getHeaders()
    {
        // Define a headers array
        $headers = array();

        foreach ($this->attributes as $key => $value) {
            // Does our server attribute have our header prefix?
            if (self::hasPrefix($key, self::$http_header_prefix)) {
                // Add our server attribute to our header array
                $headers[
                    substr($key, strlen(self::$http_header_prefix))
                ] = $value;

            } elseif (in_array($key, self::$http_nonprefixed_headers)) {
                // Add our server attribute to our header array
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}


/* End of src/Klein/DataCollection/ServerDataCollection.php */

/* -------------------- */

/* Start of src/Klein/DataCollection/ResponseCookieDataCollection.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * ResponseCookieDataCollection
 *
 * A DataCollection for HTTP response cookies
 *
 * @uses        DataCollection
 * @package     Klein\DataCollection
 */
class ResponseCookieDataCollection extends DataCollection
{

    /**
     * Methods
     */

    /**
     * Constructor
     *
     * @override (doesn't call our parent)
     * @param array $cookies The cookies of this collection
     * @access public
     */
    public function __construct(array $cookies = array())
    {
        foreach ($cookies as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set a cookie
     *
     * {@inheritdoc}
     *
     * A value may either be a string or a ResponseCookie instance
     * String values will be converted into a ResponseCookie with
     * the "name" of the cookie being set from the "key"
     *
     * Obviously, the developer is free to organize this collection
     * however they like, and can be more explicit by passing a more
     * suggested "$key" as the cookie's "domain" and passing in an
     * instance of a ResponseCookie as the "$value"
     *
     * @see DataCollection::set()
     * @param string $key                   The name of the cookie to set
     * @param ResponseCookie|string $value  The value of the cookie to set
     * @access public
     * @return ResponseCookieDataCollection
     */
    public function set($key, $value)
    {
        if (!$value instanceof ResponseCookie) {
            $value = new ResponseCookie($key, $value);
        }

        return parent::set($key, $value);
    }
}


/* End of src/Klein/DataCollection/ResponseCookieDataCollection.php */

/* -------------------- */

} /* end of namespace Klein\DataCollection */

namespace Klein\Exceptions {
use OutOfBoundsException;
use OverflowException;
use RuntimeException;
use UnexpectedValueException;

/* Start of src/Klein/Exceptions/UnhandledException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * UnhandledException
 *
 * Exception used for when a exception isn't correctly handled by the Klein error callbacks
 * 
 * @uses       Exception
 * @package    Klein\Exceptions
 */
class UnhandledException extends RuntimeException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/UnhandledException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/ValidationException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * ValidationException 
 *
 * Exception used for Validation errors
 * 
 * @uses       Exception
 * @package    Klein\Exceptions
 */
class ValidationException extends UnexpectedValueException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/ValidationException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/DispatchHaltedException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * DispatchHaltedException
 *
 * Exception used to halt a route callback from executing in a dispatch loop
 * 
 * @uses       RuntimeException
 * @package    Klein\Exceptions
 */
class DispatchHaltedException extends RuntimeException implements KleinExceptionInterface
{

    /**
     * Constants
     */

    /**
     * Skip this current match/callback
     *
     * @const int
     */
    const SKIP_THIS = 1;

    /**
     * Skip the next match/callback
     *
     * @const int
     */
    const SKIP_NEXT = 2;

    /**
     * Skip the rest of the matches
     *
     * @const int
     */
    const SKIP_REMAINING = 0;


    /**
     * Properties
     */

    /**
     * The number of next matches to skip on a "next" skip
     *
     * @var int
     * @access protected
     */
    protected $number_of_skips = 1;


    /**
     * Methods
     */

    /**
     * Gets the number of matches to skip on a "next" skip
     *
     * @return int
     */
    public function getNumberOfSkips()
    {
        return $this->number_of_skips;
    }
    
    /**
     * Sets the number of matches to skip on a "next" skip
     *
     * @param int $number_of_skips
     * @access public
     * @return DispatchHaltedException
     */
    public function setNumberOfSkips($number_of_skips)
    {
        $this->number_of_skips = (int) $number_of_skips;

        return $this;
    }
}


/* End of src/Klein/Exceptions/DispatchHaltedException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/UnknownServiceException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * UnknownServiceException
 *
 * Exception used for when a service was called that doesn't exist
 * 
 * @uses       Exception
 * @package    Klein\Exceptions
 */
class UnknownServiceException extends OutOfBoundsException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/UnknownServiceException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/LockedResponseException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * LockedResponseException
 *
 * Exception used for when a response is attempted to be modified while its locked
 * 
 * @uses       RuntimeException
 * @package    Klein\Exceptions
 */
class LockedResponseException extends RuntimeException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/LockedResponseException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/KleinExceptionInterface.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */



/**
 * KleinExceptionInterface
 *
 * Exception interface that Klein's exceptions should implement
 *
 * This is mostly for having a simple, common Interface class/namespace
 * that can be type-hinted/instance-checked against, therefore making it
 * easier to handle Klein exceptions while still allowing the different
 * exception classes to properly extend the corresponding SPL Exception type
 *
 * @package    Klein\Exceptions
 */
interface KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/KleinExceptionInterface.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/DuplicateServiceException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * DuplicateServiceException
 *
 * Exception used for when a service is attempted to be registered that already exists
 * 
 * @uses       Exception
 * @package    Klein\Exceptions
 */
class DuplicateServiceException extends OverflowException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/DuplicateServiceException.php */

/* -------------------- */

/* Start of src/Klein/Exceptions/ResponseAlreadySentException.php */

/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */





/**
 * ResponseAlreadySentException
 *
 * Exception used for when a response is attempted to be sent after its already been sent
 * 
 * @uses       RuntimeException
 * @package    Klein\Exceptions
 */
class ResponseAlreadySentException extends RuntimeException implements KleinExceptionInterface
{
}


/* End of src/Klein/Exceptions/ResponseAlreadySentException.php */

/* -------------------- */

} /* end of namespace Klein\Exceptions */
