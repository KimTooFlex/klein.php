<?php
ini_set("display_errors", true);
error_reporting(E_ALL);


$dir = "src/Klein/";


/**
 Crawl through each .php file and get the namespace defined in the file, and all dependency namespaces
 */
$aNamespaces = array();
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $finfo) {
    $fname = $finfo->getPathname();
    if ('.php' !== substr($fname, -4)) {
        continue;
    }
    $php = processFile($fname);
    $ns = $php["namespace"];
    if (!isset($aNamespaces[$ns])) {
        $aNamespaces[$ns] = array();
    }
    $aNamespaces[$ns][$fname] = $php;
}

/*
$aNamespaces: Array(
[Klein] => Array
    [src/Klein/HttpStatus.php] => Array
        [filename] => src/Klein/HttpStatus.php
        [namespace] => Klein
        [uses] => Array()
        [out] => (file content)
    [src/Klein/Response.php] => Array
        [filename] => src/Klein/Response.php
        [namespace] => Klein
        [uses] => Array
        [uses] => Array()
        [out] => (file content)
    [src/Klein/AbstractRouteFactory.php] => Array
        [filename] => src/Klein/AbstractRouteFactory.php
        [namespace] => Klein
        [uses] => Array
        [uses] => Array()
        [out] => (file content)
    [src/Klein/Klein.php] => Array
        [filename] => src/Klein/Klein.php
        [namespace] => Klein
        [uses] => Array(\Exception, \OutOfBoundsException, \Klein\DataCollection\RouteCollection, \Klein\Exceptions\LockedResponseException,
                        \Klein\Exceptions\UnhandledException, \Klein\Exceptions\DispatchHaltedException)
        [out] => (file content)
    [src/Klein/App.php] => Array
        [filename] => src/Klein/App.php
        [namespace] => Klein
        [uses] => Array(\BadMethodCallException, \Klein\Exceptions\UnknownServiceException, \Klein\Exceptions\DuplicateServiceException)
        [out] => (file content)
    [src/Klein/RouteFactory.php] => Array
        [filename] => src/Klein/RouteFactory.php
        [namespace] => Klein
        [uses] => Array()
        [out] => (file content)
    [src/Klein/ServiceProvider.php] => Array
        [filename] => src/Klein/ServiceProvider.php
        [namespace] => Klein
        [uses] => Array(\Klein\DataCollection\DataCollection)
        [out] => (file content)
    [src/Klein/Request.php] => Array
        [filename] => src/Klein/Request.php
        [namespace] => Klein
        [uses] => Array(\Klein\DataCollection\DataCollection, \Klein\DataCollection\ServerDataCollection,
                        \Klein\DataCollection\HeaderDataCollection)
        [out] => (file content)
    [src/Klein/Validator.php] => Array
        [filename] => src/Klein/Validator.php
        [namespace] => Klein
        [uses] => Array(\BadMethodCallException, \Klein\Exceptions\ValidationException)
        [out] => (file content)
    [src/Klein/AbstractResponse.php] => Array
        [filename] => src/Klein/AbstractResponse.php
        [namespace] => Klein
        [uses] => Array(Klein\DataCollection\HeaderDataCollection, Klein\DataCollection\ResponseCookieDataCollection,
                        Klein\Exceptions\LockedResponseException, Klein\Exceptions\ResponseAlreadySentException, Klein\ResponseCookie)
        [out] => (file content)
    [src/Klein/Route.php] => Array
        [filename] => src/Klein/Route.php
        [namespace] => Klein
        [uses] => Array(InvalidArgumentException)
        [out] => (file content)
    [src/Klein/ResponseCookie.php] => Array
        [filename] => src/Klein/ResponseCookie.php
        [namespace] => Klein
        [uses] => Array()
        [out] => (file content)

    [Klein\DataCollection] => Array
        [src/Klein/DataCollection/HeaderDataCollection.php] => Array
            [filename] => src/Klein/DataCollection/HeaderDataCollection.php
            [namespace] => Klein\DataCollection
            [uses] => Array()
            [out] => (file content)
        [src/Klein/DataCollection/ServerDataCollection.php] => Array
            [filename] => src/Klein/DataCollection/ServerDataCollection.php
            [namespace] => Klein\DataCollection
            [uses] => Array()
            [out] => (file content)
        [src/Klein/DataCollection/RouteCollection.php] => Array
            [filename] => src/Klein/DataCollection/RouteCollection.php
            [namespace] => Klein\DataCollection
            [uses] => Array(Klein\Route)
            [out] => (file content)
        [src/Klein/DataCollection/ResponseCookieDataCollection.php] => Array
            [filename] => src/Klein/DataCollection/ResponseCookieDataCollection.php
            [namespace] => Klein\DataCollection
            [uses] => Array(Klein\ResponseCookie)
            [out] => (file content)
        [src/Klein/DataCollection/DataCollection.php] => Array
            [filename] => src/Klein/DataCollection/DataCollection.php
            [namespace] => Klein\DataCollection
            [uses] => Array(\IteratorAggregate, \ArrayAccess, \Countable, \ArrayIterator)
            [out] => (file content)

    [Klein\Exceptions] => Array
        [src/Klein/Exceptions/UnhandledException.php] => Array
            [filename] => src/Klein/Exceptions/UnhandledException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\RuntimeException)
            [out] => (file content)
        [src/Klein/Exceptions/LockedResponseException.php] => Array
            [filename] => src/Klein/Exceptions/LockedResponseException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\RuntimeException)
            [out] => (file content)
        [src/Klein/Exceptions/KleinExceptionInterface.php] => Array
            [filename] => src/Klein/Exceptions/KleinExceptionInterface.php
            [namespace] => Klein\Exceptions
            [uses] => Array()
            [out] => (file content)
        [src/Klein/Exceptions/ResponseAlreadySentException.php] => Array
            [filename] => src/Klein/Exceptions/ResponseAlreadySentException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\RuntimeException)
            [out] => (file content)
        [src/Klein/Exceptions/ValidationException.php] => Array
            [filename] => src/Klein/Exceptions/ValidationException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\UnexpectedValueException)
            [out] => (file content)
        [src/Klein/Exceptions/DispatchHaltedException.php] => Array
            [filename] => src/Klein/Exceptions/DispatchHaltedException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\RuntimeException)
            [out] => (file content)
        [src/Klein/Exceptions/DuplicateServiceException.php] => Array
            [filename] => src/Klein/Exceptions/DuplicateServiceException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\OverflowException)
            [out] => (file content)
        [src/Klein/Exceptions/UnknownServiceException.php] => Array
            [filename] => src/Klein/Exceptions/UnknownServiceException.php
            [namespace] => Klein\Exceptions
            [uses] => Array(\OutOfBoundsException)
            [out] => (file content)
)
*/


// sort by namespace
ksort($aNamespaces);


$ret = "<?php\n";
$ret .= "/* This is an auto-generated file. Do not edit */\n";
foreach ($aNamespaces as $namespace=>$files) {
    // $namespace: name of the root namespace
    // $files: all the files for that namespace

    // compute the list of all used namespaces in those files
    $aUses = array();
    foreach ($files as $filename=>$php) {
        $aUses = array_merge($aUses, $php["uses"]);
    }
    sort($aUses);

    //ksort($files);
    // or maybe kusort by file length
    uksort($files, function($a,$b){return strlen($a)>strlen($b);});

    // output code

    $ret .= "\nnamespace $namespace {\n";

    foreach (array_unique($aUses) as $use) {
        $ret .= "use $use;\n";
    }

    foreach ($files as $filename=>$php) {
//echo "$filename\n";
        $content = $php["out"];
        $ret .= "\n/* Start of $filename */\n";
        $ret .= $content;
        $ret .= "\n/* End of $filename */\n";
        $ret .= "\n/* -------------------- */\n";
    }
    $ret .= "\n} /* end of namespace $namespace */\n";
}

echo $ret;




function processFile($filename) {
    $filecon = file_get_contents($filename, false);
//$filecon = '<? $errorCallbacks = array();';
    $tokens = token_get_all($filecon);
    unset($filecon);

    $aInstrs = array();
    $out = "";
    $namespace = "";
    $aUses = array();

    $instr = array();
    $ret = "";
    $isPhp = false;
    foreach ($tokens as $token) {
        if (is_string($token)) {    // simple 1-character token
            $ret .= $token;
            if ($isPhp) {
                $instr[] = $token;
                if (in_array($token, array(';', '{', '}'))) {
                    if (in_array(strtolower($instr[0]), array("namespace", "use"))) {
                        $part = join(array_slice($instr, 1, -1)); // skip "namespace/use" and ";"
                        if ("use" === strtolower($instr[0])) {
                            if ("\\" !== substr($part, 0, 1)) {
                                // use \namespace must begin with a \ !
                                $part = "\\" . $part;
                            }
                            $aUses[] = substr($part, 1);
                        } elseif ("" !== $namespace) {
                            // does not handle multi-namespaces file
                            throw new Exception("Namespace already specified");
                        } else {
                            $namespace = $part;
                        }
                        $ret = "";
                        $instr = array();
                        continue; // skip
                    }
                    $aInstrs[] = join(" ", $instr);
                    $instr = array();
                    $out .= $ret;
                    $ret = "";
                }
            }
        } else { // token array
            list($id, $text) = $token;
            $tokentype = token_name($id); // for debug only

            if ($isPhp && !in_array($id, array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_CLOSE_TAG))) { //TODO : test heredoc
                if (0 === count($instr)) { // flush
                    $out .= $ret;
                    $ret = "";
                }
                $instr[] = $text;
            }

            switch ($id) {
                case T_OPEN_TAG:                        // "<?php" or "<?"
                    $isPhp = true; break;
                case T_CLOSE_TAG:                       // "? >"
                    $isPhp = false; break;
            }
            $ret .= $text;
        }
    }
    $out .= $ret;
    if ($isPhp) {
        $out .= "\n?>";
    }
    $out = trim($out);

    if ('<?php' === substr($out, 0, 5)) {
        $out = substr($out, 5);
    } elseif ('<?' === substr($out, 0, 2)) {
        $out = substr($out, 2);
    } else {
        throw new Exception("File $filename does not start with T_OPEN_TAG");
    }
    if ('?>' === substr($out, -2)) {
        $out = substr($out, 0, -2);
    } else {
        throw new Exception("File $filename does not end with T_CLOSE_TAG");
    }

    return array("filename"=>$filename, "namespace"=>$namespace, "uses"=>$aUses, "out"=>$out);
}
