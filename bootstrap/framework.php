<?php

use Ampersand\AmpersandApp;
use Ampersand\API\ErrorHandler\GenericErrorHandler;
use Ampersand\API\Middleware\InitAmpersandAppMiddleware;
use Ampersand\API\Middleware\JsonRequestParser;
use Ampersand\API\Middleware\LogPerformanceMiddleware;
use Ampersand\API\Middleware\PostMaxSizeMiddleware;
use Ampersand\Frontend\AngularJSApp;
use Ampersand\Log\Logger;
use Ampersand\Misc\Settings;
use Ampersand\Misc\Shutter;
use Ampersand\Model;
use Ampersand\Plugs\MysqlConjunctCache\MysqlConjunctCache;
use Ampersand\Plugs\MysqlDB\MysqlDB;
use Cascade\Cascade;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Slim\Factory\AppFactory;
use DI\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

$shutter = new Shutter();
set_exception_handler($shutter->handleUncaughtException(...));
register_shutdown_function($shutter->shutdown(...));

$scriptStartTime = (float) microtime(true);

/**************************************************************************************************
 * PHP SESSION (Start a new, or resume the existing, PHP session)
 *************************************************************************************************/
// Allow a session ID that is never generated. This is needed because when deploying multiple containers
// for the same application, the user isn't redirected to the same container for subsequent requests.
// For more info: see comments in file src/Ampersand/Session.php
ini_set("session.use_strict_mode", '0');
ini_set("session.cookie_httponly", '1'); // ensures the cookie won't be accessible by scripting languages, such as JavaScript
if ($_SERVER['HTTPS'] ?? false) {
    ini_set("session.cookie_secure", '1'); // specifies whether cookies should only be sent over secure connections
}
session_start();

/**************************************************************************************************
 * COMPOSER AUTOLOADER
 *************************************************************************************************/
$composerAutoloaderFile = __DIR__ . '/../lib/autoload.php';
if (!file_exists($composerAutoloaderFile)) {
    throw new Exception("Cannot find autoloader for libraries at '{$composerAutoloaderFile}'. Try running 'composer install'");
}
require_once($composerAutoloaderFile);

/**************************************************************************************************
 * LOGGING
 *************************************************************************************************/
// PHP log
ini_set('error_reporting', E_ALL & ~E_NOTICE); // @phan-suppress-current-line PhanTypeMismatchArgumentInternal
ini_set("display_errors", '0');
ini_set("log_errors", '1');

// Application log
$logConfigFile = getenv('AMPERSAND_LOG_CONFIG', true);
if ($logConfigFile === false) {
    $logConfigFile = 'logging.yaml';
}
Cascade::fileConfig(dirname(__FILE__, 2) . "/config/{$logConfigFile}"); // loads logging configuration

/**************************************************************************************************
 * AMPERSAND APPLICATION
 *************************************************************************************************/
$logger = Logger::getLogger('APPLICATION');
$model = new Model(dirname(__FILE__, 2) . '/generics', $logger);

$settings = new Settings($logger); // includes default framework settings
$settings->set('global.absolutePath', dirname(__FILE__, 2));
$settings->loadSettingsFromCompiler($model); // load model settings from Ampersand compiler
$settings->loadSettingsYamlFile(dirname(__FILE__, 2) . '/config/project.yaml'); // load project specific settings
$settings->loadSettingsFromEnv();
$debugMode = $settings->get('global.debugMode');

set_time_limit($settings->get('global.scriptTimeout'));
date_default_timezone_set($settings->get('global.defaultTimezone'));

$ampersandApp = new AmpersandApp(
    $model,
    $settings,
    $logger,
    new EventDispatcher(),
    new Filesystem(
        new Local($settings->getDataDirectory()) // local file system adapter
    )
);
$ampersandApp->setFrontend(new AngularJSApp($ampersandApp));

/**************************************************************************************************
 * DATABASE and PLUGS
 *************************************************************************************************/
$mysqlDB = new MysqlDB(
    $settings->get('mysql.dbHost'),
    $settings->get('mysql.dbUser'),
    $settings->get('mysql.dbPass'),
    $settings->get('mysql.dbName', $settings->get('global.contextName')),
    Logger::getLogger('DATABASE'),
    $settings->get('global.debugMode'),
    $settings->get('global.productionEnv')
);
$ampersandApp->setDefaultStorage($mysqlDB);
$ampersandApp->setConjunctCache(new MysqlConjunctCache($mysqlDB));

/**************************************************************************************************
 * API
 *************************************************************************************************/
$apiContainer = new Container();
$apiContainer->set('ampersand_app', $ampersandApp); // add AmpersandApp object to API DI-container

// Create and configure Slim app (version 4.x)
$api = AppFactory::create(
    container: $apiContainer
);
$api->setBasePath('/api/v1');

foreach (glob(__DIR__ . '/api/*.php') as $filepath) {
    require_once($filepath);
}

/**************************************************************************************************
 * BOOTSTRAP OTHER FILES AND REGISTERED EXTENSIONS
 *************************************************************************************************/
foreach (glob(__DIR__ . '/files/*.php') as $filepath) {
    require_once($filepath);
}

foreach ($ampersandApp->getSettings()->getExtensions() as $ext) {
    $ext->bootstrap();
}

/**************************************************************************************************
 * HANDLE REQUEST
 *************************************************************************************************/
$logger = Logger::getLogger('API');

$api
->add(new LogPerformanceMiddleware($logger, 'PHASE-4 REQUEST | ')) // wrapper to log performance of request phase (PHASE-4)
->add(new InitAmpersandAppMiddleware($ampersandApp, $logger)) // initialize the AmpersandApp (PHASE-2) and Session (PHASE-3)
->add(new PostMaxSizeMiddleware()); // catch when post_max_size is exceeded

// Body parsing middleware allows to work with JSON and XML body directly in request handling
$api->addBodyParsingMiddleware(
    [ 'application/json' => new JsonRequestParser(), // overwrite default media type parser for application/json
    ]
);

// Position Middleware\RoutingMiddleware here, just before calling run() to have route information available in other middleware
$api->addRoutingMiddleware();

// Wrapper to log total performance
$api->add(new LogPerformanceMiddleware($logger, 'TOTAL PERFORMANCE | ', $scriptStartTime));

$errorMiddleware = $api->addErrorMiddleware(
    displayErrorDetails: $ampersandApp->getSettings()->get('global.debugMode'),
    logErrors: true,
    logErrorDetails: true,
    logger: $logger
);
$myErrorHandler = new GenericErrorHandler($ampersandApp, $api->getResponseFactory(), $logger);
$errorMiddleware->setDefaultErrorHandler($myErrorHandler);

$api->run();
