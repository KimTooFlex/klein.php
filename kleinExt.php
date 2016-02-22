<?php
require_once("klein.php");

/**
 * Add a route callback
 * @param mixed $args               An argument array. Hint: This works well when passing "func_get_args()"
 *  @named string[optional] $name   route name
 *  @named string $path   path to match: "path" or "GET|POST path"
 *  @named callable $callback       callable or a string "ctrl#act" or "act" that will be mapped to
 *                                  ctrlController/actionAct or defaultController/actionAct method
 * @return void
 */
function respondExt($optName_path_callback) {
    global $__routes;

    $args = (is_array($optName_path_callback)) ? ($optName_path_callback) : (func_get_args());
    $callback = array_pop($args);
    $route = array_pop($args);
    $name = array_pop($args);

    // get methods from url
    $method = null;
    if (($i=strpos($route, " "))!==false) {
        $method = substr($route, 0, $i);
        $method = explode("|", $method); // convert methods to array
        $route = trim(substr($route, $i+1));
    }

    // map "[ctrl#]act" to array("CtrlController/DefaultController", "actionAct")
    if (!is_callable($callback) && is_string($callback)) {
        $ctrl = "default";
        if (($i=strpos($callback, "#"))!==false) {
            $ctrl      = substr($callback, 0, $i);
            $callback  = substr($callback, $i+1);
        }
        $callback = function($rq, $rs, $ap) use ($ctrl, $callback) {
            cbproxy($rq, $rs, $ap, array(ucfirst($ctrl) . 'Controller', 'action' . ucfirst($callback)));
        };
    }

    //echo "url=$route, method=$method, name=$name, action=$action\n";
    // call Klein's respond with a local empty $__routes array,
    // then inject the newly created route back in the global $__routes array
    $routesBackup = $__routes;
    $__routes = array();
    respond($method, $route, $callback);
    if (null !== $name) {
        $routesBackup[$name] = $__routes[0];
    } else {
        $routesBackup[] = $__routes[0];
    }
    $__routes = $routesBackup;
}

/**
 * Internal function to auto load and instancize controllers (also handles static ones)
 * Before launching the action method, call ctrl->initialize($a, $b, $c)
 * Then call ctrl->before("action", $a, $b, $c)
 * The controllers must be on the controllers/ directory
 * Singletons controllers are detected based on the presence of a getSingleton / getInstance methods
 * @param _Request $rq
 * @param _Response $rs
 * @param _App $ap
 * @param mixed $cb
 */
function cbproxy($rq, $rs, $ap, $cb) {
    //Gb_Log::logDebug("klein: call",$cb);
    if (!class_exists($cb[0])) {
        require("controllers" . DIRECTORY_SEPARATOR . $cb[0] . '.php');
    }
    $refl = new ReflectionMethod($cb[0], $cb[1]);
    if ($refl->isStatic()) {
        //echo $cb[0] . " --- " . $cb[1] . "a=".var_export($rq,true). "b=".var_export($rs,true). "c=".var_export($ap, true);
        if (is_callable(array($cb[0], "initialize"))) {
            call_user_func(array($cb[0], "initialize"), $rq, $rs, $ap);
        }
        if (is_callable(array($cb[0], "before"))) {
            if (!call_user_func(array($cb[0], "before"), $cb[1], $rq, $rs, $ap)) {
                return;
            }
        }
        call_user_func($cb, $rq, $rs, $ap);
    } else {
        $single = null;
        foreach ( array("getSingleton", "getInstance") as $meth) {
            $cb2 = array($cb[0], $meth);
            if (is_callable($cb2)) {
                $single = call_user_func($cb2, $rq, $rs, $ap);
                break;
            }
        }
        if (null === $single) {
            $single = new $cb[0]($rq, $rs, $ap);    // construct new Object
        }
        if (is_callable(array($single, "initialize"))) {
            call_user_func(array($single, "initialize"), $rq, $rs, $ap);
        }
        if (is_callable(array($single, "before"))) {
            if (!call_user_func(array($single, "before"), $cb[1], $rq, $rs, $ap)) {
                return;
            }
        }
        call_user_func(array($single, $cb[1]), $rq, $rs, $ap);
    }
}

/**
 * simple proxy for with()
 */
function withExt($namespace, $routes) {
    with($namespace, $routes);
}

/**
 * Dispatch the request to the approriate route(s)
 * Honor BASE_URL environment variable, then call Klein's dispatch()
 */
function dispatchExt($uri = null, $req_method = null, array $params = null, $capture = false) {
    if (null === $uri) {
        if (!isset($_SERVER['REQUEST_URI'])) {
            // no request : don't do anything
            return;
        }
        $uri = substr($_SERVER['REQUEST_URI'], strlen(getenv("BASE_URL")));
    }
    // remove parameters and trailing /
    if (false !== strpos($uri, '?')) {
        $uri = strstr($uri, '?', true);
    }
    while (strlen($uri) > 1 && substr($uri, -1) === '/')  {
        $uri = substr($uri, 0, -1);
    }
    return dispatch($uri, $req_method, $params, $capture);
}

/**
 * Reversed routing
 *
 * Generate the URL for a named route. Replace regexes with supplied parameters
 * When in PlaceHolders mode, render not-passed params as [:param)
 *
 * @param string $routeName[optional]            The name of the route.
 * @param array[optional] $params                Associative array of parameters to replace placeholders with.
 * @param boolean[optional,false) $fPlaceHolders When set, generate URL with placeholders ie "/user/12/[:action]"
 * @return string                                The URL of the route with named parameters in place.
 * @throws OutOfRangeException                   if $routeName has not been registred
 * @throws InvalidArgumentException              if some mandatory params have not been passed (normal mode)
 */
function getUrl($routeName=null, $params = array(), $fPlaceHolders=false) {
    global $__routes;

    if (null === $routeName || true === $routeName) {
        return '/';
    }

    if (true === $params) { //called as ($routeName, true)
        $params = array();
        $fPlaceHolders = true;
    }

    // Check if named route exists
    if(!isset($__routes[$routeName])) {
        throw new OutOfRangeException("Route '{$routeName}' does not exist.");
    }

    // Replace named parameters
    $url = $__routes[$routeName][1];

    if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $url, $matches, PREG_SET_ORDER)) {

        foreach($matches as $match) {
            list($block, $pre, $type, $param, $optional) = $match;

            if(isset($params[$param])) { // passed argument
                $url = str_replace($block, $pre . $params[$param], $url);
            } elseif ($fPlaceHolders) { // placeholder mode: render /[:param] (remove type and optional)
                $url = str_replace($block, $pre . '[:' . $param . ']', $url);
            } elseif ($optional) {
                $url = str_replace($block, '', $url);
            } else { // not set, mandatory param
                throw new InvalidArgumentException("Param '{$param}' not set for route '{$routeName}'");
            }
        }


    }

    return $url;
}

function getUrlExt() {
    $ret = call_user_func_array('getUrl', func_get_args());
    $ret = str_replace('//', '/', $ret);
    $ret = str_replace('//', '/', $ret);

    return getenv("BASE_URL") . $ret;
}

/**
 * Class to be extended by your controllers
 * provide $this->_rq, $this->_rs, $this->_ap
 * __construct
 * initialize
 * before
 */

abstract class KleinExtController {
    /**
     * @var _Request
     */
    protected $_rq;
    /**
     * @var _Response
     */
    protected $_rs;
    /**
     * @var _App
     */
    protected $_ap;

    /**
     * Default constructor
     * @param _Request $rq
     * @param _Response $rs
     * @param _App $ap
     */
    final public function __construct(_Request $rq, _Response $rs, _App $ap) {
        $this->_rq = $rq;
        $this->_rs = $rs;
        $this->_ap = $ap;
    }


}

/**
 * Useful helpers:
 * $rs->h(): shortcut for htmlspecialchars_decode()
 * $rs->renderJSON()
 * $rs->urlScheme(): http|https
 * $rs->urlHostname(): host part of the url
 * $rs->urlBase($url): getenv("BASE_URL") . $url
 * $rs->urlPrefix($url): returns "[scheme]://[hostname]/$url"
 * $rs->isAjax()
 * $rs->redirect(): handle getenv("BASE_URL")
 */
respondExt(function( _Request $rq, _Response $rs, _App $ap){
    $rs->h = function($s) { return htmlspecialchars_decode($s, ENT_QUOTES); };

    /**
     * Renders an oject or a string, et quitte
     * @param array|string[optional] $array
     * @param integer|array[optional] $statusCode (default 200, or array(statusCode, statusText))
     * @param string[optional] $jsonp_prefix = null
     */
    $rs->renderJSON = function($out=null, $statusCode=null, $jsonp_prefix=null)use($rs) {
        // supprime tout output buffer
        $rs->discard();
        $rs->noCache();

        if (null === $out) {
            $out = "";
        }

        $statusText = null;
        if (null===$statusCode) {
            $statusCode = 200;
        } elseif (is_array($statusCode)) {
            list($statusCode, $statusText) = $statusCode;
        }

        if (null===$statusText) {
            // use default statusText
            switch ($statusCode) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default:  $text = 'Unknwown status code';
            }
            $statusText = ' ' . $text;
        }

        header("HTTP/1.1 $statusCode$statusText");

        if (is_string($out)) {
            $rs->header('Content-type: text/plain; charset=UTF-8');
            echo $out;
        } else {
            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                $json = json_encode($out, JSON_UNESCAPED_SLASHES);
            } else {
                $json = json_encode($out);
            }

            if (null !== $jsonp_prefix) {
                $rs->header('Content-Type: text/javascript'); // should ideally be application/json-p once adopted
                echo "$jsonp_prefix(" . $json . ");";
            } else {
                $rs->header('Content-Type: application/json; charset=UTF-8');
                echo $json;
            }
        }
        exit(0);
    };

    $rs->urlPrefix = function($url = '') use ($rs) {
        $urlPrefix  = $rs->urlScheme();
        $urlPrefix .= "://";
        $urlPrefix .= $rs->urlHostname();

        if (substr($url, 0, 1) !== "/") {
            $url = "/" . $url;
        }

        return $urlPrefix . $url;
    };

    $rs->urlHostname = function() {
        return isset($_SERVER["HTTP_X_FORWARDED_HOST"]) ? ($_SERVER["HTTP_X_FORWARDED_HOST"]) : ($_SERVER["HTTP_HOST"]);
    };

    $rs->urlBase = function($url = '') {
        return getenv("BASE_URL") . $url;
    };

    $rs->urlScheme = function() {
        if (    (isset($_SERVER['HTTP_X_SSL']) && 'true' === $_SERVER['HTTP_X_SSL'])
             || ("443" === $_SERVER["SERVER_PORT"])
             || ((isset($_SERVER["HTTPS"]) && "on" === $_SERVER["HTTPS"]))
           ) {
            return "https";
        }
        return "http";
    };

    $rs->isAjax = function() {
        // check if the request is an ajax call
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && "XMLHttpRequest"===$_SERVER["HTTP_X_REQUESTED_WITH"]) {
            return true;
        }
        return false;
    };

    $rs->redirect = function($url, $code = 302, $exit_after_redirect = true) {
        // Redirects the request to another URL
        if ((substr($url, 0,7) !== 'http://') && (substr($url, 0,8) !== 'https://') && (substr($url, 0, strlen(getenv("BASE_URL"))) !== getenv("BASE_URL"))) {
            $url = getenv("BASE_URL") . $url;
        }
        $this->code($code);
        $this->header("Location: $url");
        if ($exit_after_redirect) {
            exit;
        }
    };

});
