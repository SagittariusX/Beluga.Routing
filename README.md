# Beluga.Routing
Simple URL routing library.

## Installation

Install it via composer!

```shell
composer require sagittariusx/beluga.routing
```

or include it inside you're composer.json

```json
{
   "require": {
      "php": ">=7.0",
      "sagittariusx/beluga.": "^0.1.0"
   }
}
```

## Preparing the web server

For Router usage you need to tell the web server, that it should rewrite requests to not existing URL paths
to the handling PHP script.

You can do it by 2 different ways:

* Passing the not existing URL path as `GET` variable with specific name, to the script
* Passing the not existing URL path VIA `$_SERVER[ 'REQUEST_URI' ]` (best choice)

### Apache web server

For apache its really simple to handle the rewrites.

Create an file with the name `.htaccess` and put it to the folder where the rewriting should work.

But remember!

.htaccess (distributed configuration files) should only be used if you do'nt have access to the server configuration files.

.htaccess usage comes with some overhead which can be avoided.

But if you admit an server, there is no need to shown you more. You have to know it :-)

The contents of this file depends to the Router type that should be used.

#### As GET variable (RouterType::REWRITE_TO_GET)

```conf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?theURL=$1 [QSA,L]
```

The first line enables the rewrite engine. Second line declares the condition that matches all not existing file calls
and the third line matches all not existing directory calls.

The last line rewrites the matching calls to not existing files and directories to `index.php` and passes the called,
not existing URL path to the `theURL` GET variable

#### As 'REQUEST_URI' value (RouterType::REWRITE_TO_REQUEST_URI)

```conf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

The first line enables the rewrite engine. Second line declares the condition that matches all not existing file calls
and the third line matches all not existing directory calls.

The last line rewrites the matching calls to not existing files and directories to `index.php` and the called. not
existing URL path is passed to `$_SERVER[ 'REQUEST_URI' ]`

#### On errors

If the .htaccess usage triggers some server errors (e.g. error 50* or something else) you have to check:

* if apache is enabled to use .htaccess files
* if apache is enabled to use rewriting by .htaccess files (Rights for .htaccess stuff)
* if apache is enabled to use the mod_rewrite extension

The first 2 things can be handled by ensure the AllowOverwrite directive. For details see:
[Apache HTTP Server Tutorial: .htaccess files](https://httpd.apache.org/docs/current/howto/htaccess.html)

```conf
AllowOverride FileInfo
```

To check if mod rewrite is enable call this inside you're console

```shell
sudo a2enmod rewrite
```

If already enabled it outputs: `Module rewrite already enabled`

If not enabled, the rewrite module gets enabled.

If you not have access to call a console at the server contact the admin or provider and ask him if mod_rewrite
is enabled for you. If not please him to enable the mod_rewrite usage via .htaccess

### NGINX web server

This is a big TODO! :-)

but i think if you use:

```
try_files $uri $uri/ /index.php?$args;
```

…it should work to get the called URL path via `$_SERVER[ 'REQUEST_URI' ]` inside `index.php`

## Usage

```php
// Include the autoloader, created by composer
require __DIR__ . '/vendor/autoload.php';

use Beluga\Routing\Router;
use Beluga\Routing\RouterType;

// Init the router
$router = new Router(
   // The type of the router
   RouterType::REWRITE_TO_REQUEST_URI
);

// Adds an dynamic regex URI path route
$router->addRoute(
   '~^/foo/(\d+)/bar/?$~',
   function ( array $matches )
   {
      // If you need access to current Router instance can get it by Router::GetInstance()
      echo 'ID: ', $matches[ 1 ], ' OK :-)';
      exit;
   }
);

// Adds an static URI path route
$router->addRoute(
   '~^/baz/?$~',
   function ()
   {
      // If you need access to current Router instance can get it by Router::GetInstance()
      echo 'The BAZ is called!';
      exit;
   }
);

```

After defining you're routes you only should call execute() and the routes will be executed.

```php
if ( ! $router->execute() )
{
   // Showing 404 error because no router matches the defined request URI path.
}
```
