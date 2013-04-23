<?php
require_once("klein.php");

function respondExt($optName_url_action) {
    $name = null;
    $method = null;

    $args = func_get_args();
    //print_r($args);
    $callback = array_pop($args);
    $route = array_pop($args);
    $name = array_pop($args);

    // get methods from url
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
        $callback = function($a,$b,$c)use($ctrl,$callback) {
            cbproxy($a,$b,$c,array(ucfirst($ctrl) . 'Controller', 'action' . ucfirst($callback)));
        };
    }

    //echo "url=$route, method=$method, name=$name, action=$action\n";
    respond($name, $method, $route, $callback);
}

function cbproxy($a,$b,$c,$cb) {
    //Gb_Log::logDebug("klein: call",$cb);
    if (!class_exists($cb[0])) {
        require("controllers" . DIRECTORY_SEPARATOR . $cb[0] . '.php');
    }
    $refl = new ReflectionMethod($cb[0], $cb[1]);
    if ($refl->isStatic()) {
        //echo $cb[0] . " --- " . $cb[1] . "a=".var_export($a,true). "b=".var_export($b,true). "c=".var_export($c, true);
        if (is_callable(array($cb[0], "initialize"))) {
            call_user_func(array($cb[0], "initialize"), $a, $b, $c);
        }
        if (is_callable(array($cb[0], "before"))) {
            if (!call_user_func(array($cb[0], "before"), $cb[1], $a, $b, $c)) {
                return;
            }
        }
        call_user_func($cb, $a, $b, $c);
    } else {
        $single = null;
        foreach ( array("getSingleton", "getInstance") as $meth) {
            $cb2 = array($cb[0], $meth);
            if (is_callable($cb2)) {
                $single = call_user_func($cb2, $a, $b, $c);
                break;
            }
        }
        if (null === $single) {
            $single = new $cb[0]($a, $b, $c);    // construct new Object
        }
        if (is_callable(array($single, "initialize"))) {
            call_user_func(array($single, "initialize"), $a, $b, $c);
        }
        if (is_callable(array($single, "before"))) {
            if (!call_user_func(array($single, "before"), $cb[1], $a, $b, $c)) {
                return;
            }
        }
        call_user_func(array($single, $cb[1]), $a, $b, $c);
    }
}

function dispatchExt($uri = null, $req_method = null, array $params = null, $capture = false) {
    if (null === $uri) {
        if (!isset($_SERVER['REQUEST_URI'])) {
            // no request : don't do anything
            return;
        }
        $uri = substr($_SERVER['REQUEST_URI'], strlen(getenv("BASE_URL")));
    }
    return dispatch($uri, $req_method, $params, $capture);
}

function withExt($namespace, $routes) {
    with($namespace, $routes);
}

function getUrlExt() {
    $ret = call_user_func_array('getUrl', func_get_args());
    $ret = str_replace('//', '/', $ret);
    $ret = str_replace('//', '/', $ret);

    return getenv("BASE_URL") . $ret;
}

/**
 * Ordre:
 * __construct
 * initialize
 * before
 */

class KleinExtController {
    /**
     * @var Klein\_Request
     */
    protected $_rq;
    /**
     * @var Klein\_Response
     */
    protected $_rs;
    /**
     * @var Klein\_App
     */
    protected $_ap;

    /**
     * Default constructor
     * @param Klein\_Request $rq
     * @param Klein\_Response $rs
     * @param Klein\_App $ap
     */
    public function __construct(_Request $rq, _Response $rs, _App $ap) {
        $this->_rq = $rq;
        $this->_rs = $rs;
        $this->_ap = $ap;
    }


}

// response helpers
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
            if (strlen($out)) {
                echo $out;
            } else {
                echo $statusText;
            }
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
        $urlPrefix .= $rs->urlBase();

        return $urlPrefix . $url;
    };

    $rs->urlBase = function() {
        return isset($_SERVER["HTTP_X_FORWARDED_HOST"]) ? ($_SERVER["HTTP_X_FORWARDED_HOST"]) : ($_SERVER["HTTP_HOST"]);
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
