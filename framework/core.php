<?php // framework/core.php

# Define things required by the framework.
framework_definitions();

# Pull in basic configuration data.
require_once php_file('base', CONFIG_DIR);

# Error handler. Enable all warnings. ("Warnings are errors!")
require_once php_file('error_handler', FRAMEWORK_DIR);

# Functions to render views.
require_once php_file('render', FRAMEWORK_DIR);
# Functions to redirect to another page and deal with URLs.
require_once php_file('redirect', FRAMEWORK_DIR);
# Functions to set and display error/warning/status messages.
require_once php_file('messages', FRAMEWORK_DIR);


# TODO: Not sure where to set this. E_STRICT says we should set timezone before calling date().
date_default_timezone_set('America/Chicago');


/*
TODO: determine name of site. (URL, title)
TODO: Determine what "mode" we're in -- development, staging, production.
		how do we determine, how do we define differences?
TODO: login/authentication/current_user stuff (framework/login?)
TODO: authorization stuff (framework/authz?)
		logged_in(), logged_in_as_admin()
		$current_user = current_user(); $current_user->is_in_group('admin')
TODO: helpers for form elements (especially select drop-downs) (framework/forms)
        also include default state/country drop-downs, like Rails
TODO: helpers for handling/filtering/validating GET/POST data (model or controller?)
TODO: session handling -- probably just use basic PHP support.
TODO: deployment?
TODO: configuration
		config('setting'); configure('setting', 'value');
TODO: 
*/


function framework_definitions ()
{
    # TODO: Find the root of the app, containing the top-level directories.
    #define('APP_ROOT', basename($_SERVER['SCRIPT_FILENAME']) . '/../');
    define('APP_ROOT', dirname(dirname(__FILE__)));

    # Define constants for other directories used in the framework.
    define('FRAMEWORK_DIR', APP_ROOT . '/framework/');
    define('MODELS_DIR', APP_ROOT . '/models/');
    define('VIEWS_DIR', APP_ROOT . '/views/');
    define('PUBLIC_DIR', APP_ROOT . '/public/');
    define('CONTROLLERS_DIR', PUBLIC_DIR);
    define('CONFIG_DIR', APP_ROOT . '/config/');
    define('LAYOUTS_DIR', APP_ROOT . '/layouts/');
    define('PARTIALS_DIR', VIEWS_DIR . '/partials/');
    define('INCLUDES_DIR', APP_ROOT . '/includes/');
    define('LOG_DIR', APP_ROOT . '/log/');
    define('TMP_DIR', APP_ROOT . '/tmp/');
    define('TEMP_DIR', TMP_DIR);
}


# Find the file, adding '.php' if necessary. Returns NULL if file does not exist.
function php_file ( $file, $dir )
{
    # If we were passed NULL or an empty string, return NULL.
    if ( empty($file) ) { return null; }

    # If we were passed an absolute file path, return that.
    if ( '/' == $file[0] ) { return $file; }

    # Build the full path to the file.
    $file_name = $dir . '/' . $file;

    # Add '.php' to the file name if it's not already there.
    if ( !preg_match('/\.php$/', $file_name) ) { $file_name = $file_name . '.php'; }

    # TODO: See if the file exists. Return NULL if it does not exist.

    # Return the full path name to the file.
    return $file_name;
}


# Auto-load model classes.
function __autoload($class)
{
    $file = php_file($class, MODELS_DIR);

    # TODO: See if php_file returned NULL. Perhaps look in other directories.
    require_once $file;
}

