# kleinExt.php

This is an extension for [klein.php](https://github.com/chriso/klein.php) version 1.0
This repository includes Klein.php v1.x as the original, unmodified file, so there is no dependencies required.

KleinExt.php ```require()``` klein.php, so there is no need to ```require()``` both in your application.

To use the extended methods, you must use ```respondExt()```, ```dispatchExt()```, ```withExt()``` instead of the original ```respond()```, ```dispatch()```, ```with()```.


## New Features:



### Base_url
* Can be used with a prefix (getenv("BASE_URL")).
KleinExt takes care of prefixing all routes with your BASE_URL.
Suggested way to use it is to add to your .htaccess:

```php
# Same URL path two times
SetEnv BASE_URL         /url/path/to/your/application
RewriteBase             /url/path/to/your/application
```



### Cleaner route definition
The route can optionaly specify methods:

```php
respondExt("GET|POST /", function($rq, $rs, $ap){});
```



### Class auto instancing
Class auto-instancing, with ```initialize()``` and ```before()``` support. Methods can be static or instance-based. Controller class is also automatically loaded.

example :

```php
respondExt("/posts/show", "posts#show")
```

will try to find a class ```PostsController```, if it is not available, it will **require** ```controllers/PostsController.php```.
The class will be instanced if the action is not static. In that case, kleinExt will look for ```class::getInstance()``` or ```class::getSingleton```. If none of these exist, a new instance will simply be created using new class($request, $respond, $application).

The class can extend ```KleinExtController```. In that case, the action can get access to ```$this->_rq``` for the Klein Request, ```$this->_rs``` for the Klein Response and ```$this->_ap``` for the Klein Application objects.

Once instanced, kleinExt.php call ```initialize($request, $response, $application)``` if available, and then:
```before($action, $request, $response, $application)```.

```before()``` can be used to restrict access to an action. ```before()``` should return true in order for the action to be called.

The action method called must be named ```actionTheaction``` with ```Theaction``` being the action name.
So for the above example, ```dispatch()``` will call ```PostsController::actionShow()```

If the class is not availaible, kleinExt will try to include the file ```controllers/PostController.php```.



### Reversed routing

Some routes can have a *name*, so URL can be generated from the respond route.
```php
<?php

respondExt('home',       'GET|POST', '/', function($rq, $rs, $ap){});
respondExt(              'GET',      '/users/', function($rq, $rs, $ap){});
respondExt('users_show', 'GET',      '/users/[i:id]', function($rq, $rs, $ap){});
respondExt('user_do',    'POST',     '/users/[i:id]/[delete|update:action]', function($rq, $rs, $ap){});
respondExt('posts_do',   'GET',      '/posts/[create|edit:action]?/[i:id]?', function($rq, $rs, $ap){});
?>
```

*Example* - Generating URL for immediate consumption

```php
<?php

getUrlExt('home');                                            // "/"
getUrlExt('users_show', array('id' => 14));                   // "/users/14"
getUrlExt('user_do', array('id' => 17, 'action'=>'delete'));  // "/users/17/delete"
getUrlExt('user_do', array('id' => 17));                      // Exception "Param 'action' not set for route 'user_do'"
getUrlExt('posts_do', array('id' => 16));                     // "/posts/16" (note that it isn't /posts//16)
getUrlExt('posts_do', array('action' => 'edit', 'id' => 15)); // "/posts/edit/15"
?>
```

*Example* - Generating URL for later use (placeholder mode)

This mode allows to generate URL that can be templated elsewhere.
To activate this mode, use ```getUrlExt``` with a new last parameter set to 'true'
```php
<?php

getUrlExt('users_show', array(), true);                            // "/users/[:id]"
getUrlExt('users_show', true);                                     // "/users/[:id]" (shorter notation)
getUrlExt('posts_do', array('id' => 15), true);                    // "/posts/[:action]/15"
getUrlExt('posts_do', array('action' => "edit"), true);            // "/posts/edit/[:id]"
?>
```



### View helpers:
The folowing view helpers are automatically added:

*  $rs->h(): shortcut for htmlspecialchars_decode()
*  $rs->renderJSON(): exits and render a string/array with a custom http code
*  $rs->urlPrefix(): returns the host part of the url
*  $rs->urlBase(): returns the url path to the root of the application, without the scheme
*  $rs->urlScheme(): returns "http" / "https"
*  $rs->isAjax(): returns boolean
*  $rs->redirect(): same as klein.php original redirect, but handles absolute URL and BASE_URL




## Contributors

kleinExt.php is based on the work of:
- [Chris O'Hara](https://github.com/chriso)
- [Trevor N. Suarez](https://github.com/Rican7)




## License

(MIT License)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

