<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 */
if (!function_exists('bootstrap_env_value')) {
	function bootstrap_env_value($key, $default = null)
	{
		if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
			return $_SERVER[$key];
		}

		$envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
		if (!is_readable($envFile)) {
			return $default;
		}

		foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
			$line = trim($line);
			if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
				continue;
			}

			list($name, $value) = explode('=', $line, 2);
			if (trim($name) !== $key) {
				continue;
			}

			return trim(trim($value), "\"'");
		}

		return $default;
	}
}

if (!function_exists('bootstrap_env_bool')) {
	function bootstrap_env_bool($key, $default = false)
	{
		$value = bootstrap_env_value($key, null);
		if ($value === null) {
			return $default;
		}

		$normalized = strtolower(trim((string) $value));

		if (in_array($normalized, array('1', 'true', 'yes', 'on'), true)) {
			return true;
		}

		if (in_array($normalized, array('0', 'false', 'no', 'off'), true)) {
			return false;
		}

		return $default;
	}
}

define('ENVIRONMENT', bootstrap_env_value('CI_ENV', 'development'));


/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch (ENVIRONMENT) {
	case 'development':
		error_reporting(0);
		// error_reporting(-1);
		// ini_set('display_errors', 1);
		break;

	case 'testing':
	case 'production':
		ini_set('display_errors', 0);
		if (version_compare(PHP_VERSION, '5.3', '>=')) {
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		} else {
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
		}
		break;

	default:
		header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
		echo 'The application environment is not set correctly.';
		exit(1); // EXIT_ERROR
}

/*
 *---------------------------------------------------------------
 * SYSTEM DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" directory.
 * Set the path if it is not in the same directory as this file.
 */
$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * directory than the default one you can set its name here. The directory
 * can also be renamed or relocated anywhere on your server. If you do,
 * use an absolute (full) server path.
 * For more info please see the user guide:
 *
 * https://codeigniter.com/userguide3/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 */
$application_folder = 'application';

/*
 *---------------------------------------------------------------
 * VIEW DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want to move the view directory out of the application
 * directory, set the path to it here. The directory can be renamed
 * and relocated anywhere on your server. If blank, it will default
 * to the standard location inside your application directory.
 * If you do move this, use an absolute (full) server path.
 *
 * NO TRAILING SLASH!
 */
$view_folder = '';


/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here. For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT: If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller. Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 */
// The directory name, relative to the "controllers" directory.  Leave blank
// if your controller is not in a sub-directory within the "controllers" one
// $routing['directory'] = '';

// The controller class file name.  Example:  mycontroller
// $routing['controller'] = '';

// The controller function you wish to be called.
// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 */
// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

// Set the current directory correctly for CLI requests
if (defined('STDIN')) {
	chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== FALSE) {
	$system_path = $_temp . DIRECTORY_SEPARATOR;
} else {
	// Ensure there's a trailing slash
	$system_path = strtr(
		rtrim($system_path, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	) . DIRECTORY_SEPARATOR;
}

// Is the system path correct?
if (!is_dir($system_path)) {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: ' . pathinfo(__FILE__, PATHINFO_BASENAME);
	exit(3); // EXIT_CONFIG
}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// Path to the system directory
define('BASEPATH', $system_path);

// Path to the front controller (this file) directory
define('FCPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Name of the "system" directory
define('SYSDIR', basename(BASEPATH));

// The path to the "application" directory
if (is_dir($application_folder)) {
	if (($_temp = realpath($application_folder)) !== FALSE) {
		$application_folder = $_temp;
	} else {
		$application_folder = strtr(
			rtrim($application_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
		);
	}
} elseif (is_dir(BASEPATH . $application_folder . DIRECTORY_SEPARATOR)) {
	$application_folder = BASEPATH . strtr(
		trim($application_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	);
} else {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: ' . SELF;
	exit(3); // EXIT_CONFIG
}

define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);

// The path to the "views" directory
if (!isset($view_folder[0]) && is_dir(APPPATH . 'views' . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . 'views';
} elseif (is_dir($view_folder)) {
	if (($_temp = realpath($view_folder)) !== FALSE) {
		$view_folder = $_temp;
	} else {
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
		);
	}
} elseif (is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . strtr(
		trim($view_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	);
} else {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: ' . SELF;
	exit(3); // EXIT_CONFIG
}

define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);

$envHelper = APPPATH . 'helpers/env_helper.php';
if (is_readable($envHelper)) {
	require_once $envHelper;
}

$vendorAutoload = FCPATH . 'vendor/autoload.php';
if (is_readable($vendorAutoload)) {
	require_once $vendorAutoload;
}

if (function_exists('env')
	&& class_exists('\\Sentry\\State\\HubInterface')
	&& !defined('SENTRY_INITIALIZED')
) {
	$sentryDsn = env('SENTRY_DSN', '');
	if ($sentryDsn !== '') {
		$traceSampleRate = env('SENTRY_TRACES_SAMPLE_RATE', ENVIRONMENT === 'development' ? 1.0 : 0.0);
		$traceSampleRate = is_numeric($traceSampleRate) ? (float) $traceSampleRate : (ENVIRONMENT === 'development' ? 1.0 : 0.0);

		\Sentry\init(array(
			'dsn' => $sentryDsn,
			'environment' => env('SENTRY_ENVIRONMENT', env('CI_ENV', ENVIRONMENT)),
			'release' => env('SENTRY_RELEASE', null),
			'attach_stacktrace' => true,
			'traces_sample_rate' => max(0.0, min(1.0, $traceSampleRate)),
		));

		define('SENTRY_INITIALIZED', true);
	}
}

if (defined('SENTRY_INITIALIZED')
	&& class_exists('\\Sentry\\Tracing\\TransactionContext')
	&& class_exists('\\Sentry\\Tracing\\TransactionSource')
	&& function_exists('\\Sentry\\startTransaction')
	&& (!isset($_SERVER['argv']) || PHP_SAPI !== 'cli')
) {
		$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'CLI';
		$requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
		$transactionName = trim($requestMethod . ' ' . $requestUri);

		$transactionContext = \Sentry\Tracing\TransactionContext::make()
			->setName($transactionName !== '' ? $transactionName : 'HTTP request')
			->setOp('http.server')
			->setSource(\Sentry\Tracing\TransactionSource::url());

		$bootstrapTransaction = \Sentry\startTransaction($transactionContext);
		$bootstrapTransaction->setData(array(
			'http.method' => $requestMethod,
			'http.request_uri' => $requestUri,
			'server_name' => $_SERVER['SERVER_NAME'] ?? null,
			'query_string' => $_SERVER['QUERY_STRING'] ?? null,
		));

		\Sentry\configureScope(static function (\Sentry\State\Scope $scope) use ($bootstrapTransaction): void {
			$scope->setSpan($bootstrapTransaction);
		});

		register_shutdown_function(static function () use ($bootstrapTransaction): void {
			$statusCode = (int) http_response_code();
			if ($statusCode <= 0) {
				$statusCode = 200;
			}

			$lastError = error_get_last();
			if ($lastError !== null && in_array($lastError['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
				$statusCode = 500;
				$bootstrapTransaction->setStatus(\Sentry\Tracing\SpanStatus::internalError());
				$bootstrapTransaction->setData(array(
					'php.shutdown_error' => array(
						'type' => $lastError['type'],
						'message' => $lastError['message'],
						'file' => $lastError['file'],
						'line' => $lastError['line'],
					),
				));
			} else {
				$bootstrapTransaction->setHttpStatus($statusCode);
			}

			$bootstrapTransaction->setData(array(
				'http.status_code' => $statusCode,
			));

			$bootstrapTransaction->finish();

			\Sentry\configureScope(static function (\Sentry\State\Scope $scope): void {
				$scope->setSpan(null);
			});
		});
}

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 */
require_once BASEPATH . 'core/CodeIgniter.php';
