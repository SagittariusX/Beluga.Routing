<?php
/**
 * @author         SagittariusX <unikado+sag@gmail.com>
 * @copyright  (c) 2016, SagittariusX
 * @package        Beluga
 * @since          2016-08-08
 * @subpackage     Routing
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Beluga\Routing;


use Beluga\ArgumentError;
use Beluga\DynamicProperties\ExplicitGetterSetter;



/**
 * This class should be used as a Router.
 *
 * The usage is really simple. First you have to create a new instance or get the existing global instance
 * if you have already initialized a instance before, in current script run.
 *
 * <code>
 * $router = new \Beluga\Routing\Router(
 *    RouterType::CUSTOM,
 *    '',
 *    '/foo/45887/bar'
 * );
 * </code>
 *
 * In this case i use a custom URI path. In real case you should prefer the types
 * {@see \Beluga\Routing\RouterType::REWRITE_TO_GET} or
 * {@see \Beluga\Routing\RouterType::REWRITE_TO_REQUEST_URI}.
 *
 * Now we should add one or more routes that handles the different Request URI paths of our application.
 *
 * <code>
 * $router->addRoute(
 *    '~^/(index\.(html|php))?$~i',
 *    function ( array $matches )
 *    {
 *       // If you need access to current Router instance can get it by \Beluga\Routing\Router::GetInstance()
 *       exit( 'You have called you\'re application home. Welcome!' );
 *    }
 * );
 *
 * $router->addRoute(
 *    '~^/foo/(\d+)/bar/?$~',
 *    function ( array $matches )
 *    {
 *       // If you need access to current Router instance can get it by \Beluga\Routing\Router::GetInstance()
 *       exit( 'ID: ' . $matches[ 1 ] . ' OK :-)' );
 *    }
 * );
 * </code>
 *
 * After defining you're routes you only should call execute() and you're route will be executed.
 *
 * <code>
 * if ( ! $router->execute() )
 * {
 *    // Showing 404 error because no router matches the defined request URI path.
 * }
 * </code>
 *
 * @since v0.1.0
 * @property-read string  $requestUrlPath The currently called raw request url path.
 * @property-read array   $elements       All elements of the currently called URL path, excluding home.
 * @property-read int     $level          The current max. URL path element count, excluding home.
 * @property-read string  $getFieldName   The name of the URL GET parameter that should contain the called URL
 *                                        path if current type is
 *                                        {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_REQUEST_URI}.
 * @property-read string  $type           The current router type. Can be
 *                                        {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_GET} or
 *                                        {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_GET} or
 *                                        {@see \Beluga\Routing\Router::TYPE_CUSTOM}.
 * @property-read string  $extension      The file name extension of the requested url path. (e.g.: html or php)
 * @property-read integer $requestType    The Type of the requested URL path, depending to a may defined file
 *                                        name extension. Usable Values are defined by the
 *                                        {@see \Beluga\Routing\Router}::REQUEST_FOR_* constants.
 * @property      array   $homeNames      The url elements defined here, are usable as pointing to applications
 *                                        home. Default values are '', '/', 'index', 'index.html', 'index.php'.
 * @property-read bool    $isHome         Returns if the current request url path points to the application home.
 */
class Router extends ExplicitGetterSetter
{


   # <editor-fold desc="= = =   P R I V A T E   F I E L D S   = = = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Holds all registered DYNAMIC routes of current router.
    *
    * @var array
    */
   private $routes = [];

   /**
    * Holds all registered STATIC routes of current router.
    *
    * @var array
    */
   private $staticRoutes = [];

   # </editor-fold>


   # <editor-fold desc="= = =   P R O T E C T E D   F I E L D S   = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * All class properties.
    *
    * - <b>requestUrlPath</b> (string): The currently called raw request url path.
    * - <b>elements</b> (array):        All elements of the currently called URL path, excluding home.
    * - <b>level</b> (integer):         The current max. URL path element count, excluding home.
    * - <b>getFieldName</b> (string):   The name of the URL GET parameter that should contain the called URL
    *                                   path if current type is
    *                                   {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_REQUEST_URI}.
    * - <b>type</b> (string):           The current router type. Can be
    *                                   {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_GET} or
    *                                   {@see \Beluga\Routing\Router::TYPE_REWRITE_TO_GET} or
    *                                   {@see \Beluga\Routing\Router::TYPE_CUSTOM}.
    * - <b>extension</b> (string):      The file name extension of the requested url path. (e.g.: html or php)
    * - <b>request</b> (integer):       The Type of the requested URL path, depending to a may defined file
    *                                   name extension. Usable Values are defined by the
    *                                   {@see \Beluga\Routing\Router}::REQUEST_FOR_* constants.
    * - <b>homeNames</b> (array):       The url elements defined here, are usable as pointing to applications
    *                                   home. Default values are '', '/', 'index', 'index.html', 'index.php'.
    *
    * @var array
    */
   protected $properties = [
      'requestUrlPath' => '/',
      'elements'       => [ ],
      'level'          => 0,
      'getFieldName'   => 'url',
      'type'           => RouterType::REWRITE_TO_GET,
      'extension'      => 'html',
      'homeNames'      => [ '', '/', 'index', 'index.html', 'index.php' ]
   ];

   # </editor-fold>


   # <editor-fold desc="= = =   P R I V A T E   S T A T I C   F I E L D S   = = = = = = = = = = = = = = = = = =">

   /**
    * The singleton instance.
    *
    * @var \Beluga\Routing\Router
    */
   private static $instance = null;

   # </editor-fold>


   # <editor-fold desc="= = =   P U B L I C   C O N S T U C T O R   = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Init a new instance and registers it as global instance if none is currently defined.
    *
    * @param string $type         The Type of the Router. It means where the request url will be extracted from.
    *                             For it you can use one of the {@see \Beluga\Routing\RouterType} class constants.
    * @param string $urlParamName The name of the URL parameter that should define the request url path,
    *                             if $type is {@see \Beluga\Routing\RouterType::REWRITE_TO_GET}
    * @param string $customUrl    If Type is {@see \Beluga\Routing\RouterType::CUSTOM} here you have to define
    *                             the custom request url path to use for routing
    * @param array  $homeNames
    */
   public function __construct(
      string $type, string $urlParamName = 'url', string $customUrl = null, array $homeNames = [] )
   {

      if ( \count( $homeNames ) > 0 )
      {
         $this->properties[ 'homeNames' ] = $homeNames;
      }

      // Load the current instance data
      $this->reload( $type, $urlParamName, $customUrl );

      if ( \is_null( self::$instance ) )
      {
         // There is no global instance defined. Use current instance
         self::$instance = $this;
      }

   }

   # </editor-fold>


   # <editor-fold desc="= = =   P U B L I C   M E T H O D S   = = = = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Reloads the Request information from declared source.
    *
    * @param string $type          The Type of the Router. It means where the request url will be extracted from.
    *                              For it you can use one of the {@see \Beluga\Routing\RouterType} class constants
    * @param string $urlParamName  The name of the URL parameter that should define the request url path,
    *                              if $type is {@see \Beluga\Routing\RouterType::REWRITE_TO_GET}
    * @param string $customUrlPath If Type is {@see \Beluga\Routing\RouterType::CUSTOM} here you have to define
    *                              the custom request url path to use for routing
    */
   public function reload( string $type, string $urlParamName = 'url', string $customUrlPath = null )
   {

      $urlData = null;

      $this->properties[ 'type' ]         = $type;
      $this->properties[ 'getFieldName' ] = $urlParamName;

      if ( $this->properties[ 'type' ] == RouterType::CUSTOM )
      {
         // Get request url path from $customUrlPath
         $urlData = empty( $customUrlPath ) ? '' : \trim( $customUrlPath, '/' );
      }

      if ( $this->properties[ 'type' ] == RouterType::REWRITE_TO_GET )
      {
         // $_GET[ $this->properties[ 'getFieldName' ] ] should define the request url path
         if ( empty( $this->properties[ 'getFieldName' ] ) )
         {
            // If $this->properties[ 'getFieldName' ] is empty use the default 'url'
            $this->properties[ 'getFieldName' ] = 'url';
         }
         if ( \filter_has_var( \INPUT_GET , $this->properties[ 'getFieldName' ] ) )
         {
            // $_GET[ $this->properties[ 'getFieldName' ] ] is defined, use it
            $urlData = \trim(
               \filter_input(
                  \INPUT_GET,
                  $this->properties[ 'getFieldName' ],
                  \FILTER_SANITIZE_STRING
               ),
               '/'
            );
         }
      }

      if ( \is_null( $urlData ) )
      {
         // Currently no request url path is defined, get it from $_SERVER[ 'REQUEST_URI' ] if defined.
         if ( isset( $_SERVER[ 'REQUEST_URI' ] ) )
         {
            // Using $_SERVER[ 'REQUEST_URI' ]
            $urlData = \preg_replace(
               '#[^A-Za-z0-9_.,:/!~-]+#',
               '',
               \trim( $_SERVER[ 'REQUEST_URI' ], '/' )
            );
            $this->properties[ 'type' ] = RouterType::REWRITE_TO_REQUEST_URI;
         }
         else
         {
            // Use a empty fallback
            $urlData = '';
         }
      }

      if ( \strlen( $urlData ) < 1 )
      {
         // Its a home request
         $this->properties[ 'requestUrlPath' ] = '/';
         $this->properties[ 'elements' ]       = [];
         $this->properties[ 'level' ]          = 0;
         $this->setExt();
         return;
      }

      // Remove URL-Parameters
      $urlData = \explode( '?', $urlData, 2 )[ 0 ];

      // Remove Anchor
      $urlData = \explode( '#', $urlData, 2 )[ 0 ];

      // Remove some bad characters
      $urlData = \preg_replace( '#[^A-Za-z0-9_,.:/!~-]+#', '', $urlData );

      // Fix multiple following dots .. or slashes // to its single representation (Not allows ../../hidden)
      $urlData = \trim(
         \preg_replace(
            [ '~/{2,}~', '~\.{2,}~' ],
            [ '/',       '.' ],
            $urlData
         ),
         '/'
      );

      if ( empty( $urlData ) )
      {
         // Its a home request
         $this->properties[ 'requestUrlPath' ] = '/';
         $this->properties[ 'elements' ]       = [];
         $this->properties[ 'level' ]          = 0;
         $this->setExt();
         return;
      }

      // extract the url path elements
      $elements = \explode( '/', $urlData );

      if ( \count( $elements ) < 2 )
      {
         // There is only a single element
         $this->properties[ 'requestUrlPath' ] = '/' . $urlData . '/';
         $this->properties[ 'level' ]          = 1;
         $this->properties[ 'elements' ]       = $this->setExt( $elements );
         return;
      }

      // We have multiple path elements
      $this->properties[ 'requestUrlPath' ] = '/' . \join( '/', $elements );
      $this->properties[ 'elements' ]       = $this->setExt( $elements );
      $this->properties[ 'level' ]          = \count( $this->properties[ 'elements' ] );

   }

   /**
    * Removes the first URL part. (e.g.: 'foo/bar/baz' => 'bar/baz')
    *
    * @return \Beluga\Routing\Router
    */
   public final function incrementRoot() : Router
   {

      if ( \count( $this->properties[ 'elements' ] ) < 1 )
      {
         return $this;
      }

      $this->properties[ 'elements' ] = \array_shift( $this->properties[ 'elements' ] );
      --$this->properties[ 'level' ];

      return $this;

   }

   /**
    * Sets a route callback to call if a request URI path is called that matches the regexp, defined by
    * $uriFormat.
    *
    * The callback must be a function/method that accepts the following parameters:
    *
    * 1. $matches (array)             Contains the matching groups, defined by associated URI path format regexp.
    *
    * @param  string   $uriFormat     Here you have to define the URI path format, that must match, if the
    *                                 associated route callback. You must define it as a valid PHP
    *                                 regular expression. each defined match group (...) is permitted to the
    *                                 route callback as a part of a zero indexed array. Element with index 0
    *                                 is the string that matches the whole regex. Element at index 1 is the
    *                                 matching group, etc. pp. E.g.: ~^/(foo)/(\d+)(/index\.(html|php))?$~i
    * @param  callable $routeCallback The callable to call the route handling if associated $uriFormat matches
    *                                 to current called URL. It must accept at least the following parameter
    *                                 First parameter $matches contains the matching groups, defined by
    *                                 $uriFormat.
    * @return \Beluga\Routing\Router
    * @throws \Beluga\ArgumentError   If $routeCallback is not callable.
    */
   public final function addRoute( string $uriFormat, $routeCallback ) : Router
   {

      if ( ! \is_callable( $routeCallback ) )
      {
         throw new ArgumentError(
            'routeCallback', $routeCallback, 'Routing', 'Can not assign a Router route that is not of callable type!'
         );
      }

      $this->routes[ $uriFormat ] = $routeCallback;

      return $this;

   }

   /**
    * Sets a route callback to call if a request URI path is called that is exactly the declared route string.
    *
    * The callback must be a function/method that accepts the following parameters:
    *
    * 1. $matches (array)             Contains the matching groups, defined by associated URI path format regexp.
    *
    * @param  string   $routeString   Here you have to define the URI path string that is matched by this router.
    *                                 e.G.: 'foo/bar'
    * @param  callable $routeCallback The callable to call the route handling if current called URL matches.
    *                                 It must accept at least the following parameter First parameter $matches contains
    *                                 the matching groups.
    * @return \Beluga\Routing\Router
    * @throws \Beluga\ArgumentError   If $routeCallback is not callable.
    */
   public final function addRouteSimple( string $routeString, $routeCallback ) : Router
   {

      if ( ! \is_callable( $routeCallback ) )
      {
         throw new ArgumentError(
            'routeCallback', $routeCallback, 'Routing', 'Can not assign a Router route that is not of callable type!'
         );
      }

      $this->staticRoutes[ '/' . trim( $routeString, '/' ) ] = $routeCallback;
      $this->staticRoutes[ '/' . trim( $routeString, '/' ) . '/' ] = $routeCallback;

      return $this;

   }

   /**
    * Finds the route associated to current called request URI path and executes the
    * route callback of the found route. If no route was found FALSE ist returned.
    *
    * @return boolean
    */
   public final function execute() : bool
   {

      $matches = null;

      foreach ( $this->staticRoutes as $uriPath => $callback )
      {
         if ( $this->properties[ 'requestUrlPath' ] === $uriPath )
         {
            \call_user_func( $callback );
            return true;
         }
      }

      foreach ( $this->routes as $uriRegex => $callback )
      {
         if ( \preg_match( $uriRegex, $this->properties[ 'requestUrlPath' ], $matches ) )
         {
            \call_user_func(
               $callback,
               $matches
            );
            return true;
         }
      }

      return false;

   }

   # </editor-fold>


   # <editor-fold desc="= = =   G E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Returns the currently called raw request url path.
    *
    * @return string
    */
   public function getRequestUrlPath() : string
   {

      return $this->properties[ 'requestUrlPath' ];

   }

   /**
    * Returns all elements of the currently called URL path, excluding home.
    *
    * @return array
    */
   public function getElements() : array
   {

      return $this->properties[ 'elements' ];

   }

   /**
    * Returns the current max. URL path element count, excluding home.
    *
    * @return int
    */
   public function getLevel() : int
   {

      return $this->properties[ 'level' ];

   }

   /**
    * Returns the name of the URL GET parameter that should contain the called URL path if current type is
    * {@see \Beluga\Routing\RouterType::REWRITE_TO_REQUEST_URI}.
    *
    * @return string
    */
   public function getGetFieldName()
   {

      return $this->properties[ 'getFieldName' ];

   }

   /**
    * Returns the current router type. Can be {@see \Beluga\Routing\RouterType::REWRITE_TO_GET} or
    * {@see \Beluga\Routing\RouterType::REWRITE_TO_GET} or {@see \Beluga\Routing\RouterType::CUSTOM}.
    *
    * @return string
    */
   public function getType() : string
   {

      return $this->properties[ 'type' ];

   }

   /**
    * Returns the file name extension of the requested url path. (e.g.: html or php)
    *
    * @return string
    */
   public function getExtension() : string
   {

      return $this->properties[ 'extension' ];

   }

   /**
    * Returns the url elements defined here, are usable as pointing to applications home.
    *
    * Default values are '', '/', 'index', 'index.html', 'index.php'.
    *
    * @return array
    */
   public function getHomeNames() : array
   {

      return $this->properties[ 'homeNames' ];

   }

   /**
    * Returns if the current request url path points to the application home.
    *
    * @return bool
    */
   public function getIsHome() : bool
   {

      if ( $this->properties[ 'level' ] == 0 )
      {
         return true;
      }

      if ( ( $this->properties[ 'level' ] == 1 )
           && ( \count( $this->properties[ 'homeNames' ] ) > 0 ) )
      {
         foreach ( $this->properties[ 'homeNames' ] as $oh )
         {
            if ( $this->properties[ 'elements' ][ 0 ] == $oh )
            {
               return true;
            }
         }
      }

      return false;

   }

   # </editor-fold>


   # <editor-fold desc="= = =   S E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Sets the url elements, usable as pointing to applications home.
    *
    * Default values are '', '/', 'index', 'index.html', 'index.php'.
    *
    * @param array $homeNames THe home names array.
    * @return bool
    */
   public function setHomeNames( array $homeNames ) : bool
   {

      if ( count( $homeNames ) < 1 )
      {
         return false;
      }

      $this->properties[ 'homeNames' ] = $homeNames;

      return true;

   }

   # </editor-fold>


   # <editor-fold desc="= = =   P R I V A T E   M E T H O D S   = = = = = = = = = = = = = = = = = = = = = = = = =">

   private function setExt( array $elements = [] )
   {
      if ( \count( $elements ) > 0 )
      {
         $tmp = \explode( '.', $elements[ \count( $elements ) - 1 ] );
         $tsz = \count( $tmp );
         if ( $tsz > 1 )
         {
            $tszm1 = $tsz-1;
            if ( $tsz                 > 2
                 && $tmp[ $tszm1 ]   == 'gz'
                 && $tmp[ $tsz - 2 ] == 'tar' )
            {
               $this->properties[ 'extension' ] = 'tar.gz';
               \array_pop( $tmp );
               \array_pop( $tmp );
               $elements[ \count( $elements ) - 1 ] = \join( '.', $tmp );
               return $elements;
            }
            if ( \preg_match( '~^(html|php|xml|css|php|js|asp|jsp|gif|png|jpe?g)$~i', $tmp[ $tszm1 ] ) )
            {
               $this->properties[ 'extension' ] = \array_pop( $tmp );
               $elements[ \count( $elements ) - 1 ] = \join( '.', $tmp );
               if ( \strlen( $this->properties[ 'extension' ] ) > 5 )
               {
                  $this->properties[ 'extension' ] = 'html';
               }
               return $elements;
            }
            $this->properties[ 'extension' ] = 'html';
            return $elements;
         }
         $this->properties[ 'extension' ] = 'html';
         return $elements;
      }
      $this->properties[ 'extension' ] = 'html';
      return $elements;
   }

   # </editor-fold>


   # <editor-fold desc="= = =   P U B L I C   S T A T I C   M E T H O D S   = = = = = = = = = = = = = = = = = = =">

   /**
    * Returns the currently registered global Router instance.
    *
    * @return \Beluga\Routing\Router
    */
   public static function GetInstance()
   {
      return self::$instance;
   }

   /**
    * Returns if a usable global instance exists.
    *
    * @return boolean
    */
   public static function HasInstance() : bool
   {
      return ! \is_null( self::$instance );
   }

   /**
    * Returns if the current request url path points to the application home.
    *
    * @return bool
    */
   public static function IsHome() : bool
   {

      if ( ! static::HasInstance() )
      {
         return true;
      }

      return static::GetInstance()->getIsHome();

   }

   /**
    * Returns the URL depending to defined URL path level. Level 0 means the home URL /. If no level is defined
    * the whole URL is returned.
    *
    * If the level is higher then allowed or lower than 0 boolean FALSE is returned.
    *
    * @param  int $maxLevel Ammount of max returned URL path elements/parts. (0=/  1=/xyz/  etc.)
    * @return string Returns the URL or boolean FALSE if $level is outside the usable range.
    */
   public static function GetUrl( int $maxLevel = null )
   {

      if ( ! static::HasInstance() )
      {
         return '/';
      }

      $instance = static::GetInstance();

      if ( \is_null( $maxLevel ) )
      {
         return $instance->properties[ 'requestUrlPath' ];
      }

      if ( $maxLevel < 0 )
      {
         return false;
      }

      if ( $maxLevel > $instance->properties[ 'level' ] )
      {
         return $instance->properties[ 'requestUrlPath' ];
      }

      $start = '/';
      if ( self::IsHome() )
      {
         return $start;
      }

      if ( $maxLevel == $instance->properties[ 'level' ] )
      {
         return $instance->properties[ 'requestUrlPath' ];
      }

      $elementsCopy = $instance->properties[ 'elements' ];
      \array_splice( $elementsCopy, 0, $maxLevel );

      return $start . \join( '/', $elementsCopy );

   }

   /**
    * Returns the name of the URL element/part with defined index.
    *
    * @param int $levelIndex THe index of the URL level (0-n)
    * @return string or boolean FALSE
    */
   public static function GetLevelName( int $levelIndex = null )
   {

      if ( ! static::HasInstance() )
      {
         return false;
      }

      $instance = static::GetInstance();

      if ( $instance->properties[ 'level' ] < 1 )
      {
         return false;
      }

      if ( \is_null( $levelIndex ) )
      {
         $levelIndex = $instance->properties[ 'level' ] - 1;
      }
      else
      {
         --$levelIndex;
      }

      if ( $levelIndex < 0 || $levelIndex >= $instance->properties[ 'level' ] )
      {
         return false;
      }

      return $instance->properties[ 'elements' ][ $levelIndex ];

   }

   /**
    * Gets the max. accepted URL level index. (0 is always Home)
    *
    * @return int
    */
   public static function GetMaximalLevel() : int
   {

      if ( ! static::HasInstance() )
      {
         return 0;
      }

      return \intval( static::GetInstance()->properties[ 'level' ] );

   }

   /**
    * Gets the owning URL. It means the last URL path part is removed. e.g.: /abc/def/ghi/ => /abc/def/
    *
    * @return string
    */
   public static function GetOwningUrl()
   {

      return static::GetUrl( static::GetMaximalLevel() - 1 );

   }

   # </editor-fold>


}

