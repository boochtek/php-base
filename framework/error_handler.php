<?php

## error_handler.php - by Craig Buchek, 2007-06-10.

# Make sure we've got the dependencies we require. Assume that the framework core has been loaded.
require_once php_file('base', CONFIG_DIR);


## TODO: Determine site name to display in error emails.
## TODO: Allow specifying globals not to dump; if they're recursive, var_export has problems.
## TODO: Find framework-specific way to handle $_SERVER['ENVIRONMENT'].
## TODO: Don't send emails if we're just in development mode.
## TODO: Figure out why calling an undefined function isn't getting handled. (When testing from command-line.)
## TODO: Check variables before using them in here, especially $_SERVER, which doesn't exist for command-line invocation.
## TODO: Move EXCLUDE_GLOBALS to an include file. (Use here, and in render.php:render().)


# Tell PHP to trap all error types. NOTE: PHP 5 doesn't include E_STRICT in E_ALL.
error_reporting(E_ALL | E_STRICT ^ (!$CONFIG['ignore_errors']));

# This is not necessary, since we set an error handler (that does not return false).
# But it's good to set it anyway, just in case.
# TODO: Only turn this off if we're in production.
#ini_set('display_errors', 0);

# Set a custom error handler, so we can send ourselves an email (and more info) for any errors.
set_error_handler('custom_error_handler');



function custom_error_handler ( $errno, $errstr, $errfile, $errline, $errcontext )
{
    global $EMAIL;

	# If the error is not of a type we were looking for, fall back to default error handler.
	$errno = $errno & error_reporting();
	if ( $errno == 0 )
	{
		# Returning false tells PHP to fall back to the default error handler.
		return false;
	}

	# Unless the error is just a warning or notice, let the user know what happened.
	if ( $errno != E_WARNING && $errno != E_NOTICE )
	{
		# Indicate that we had some sort of internal error on the server.
		header('HTTP/1.0 500 Internal Server Error');
?>
        <html>
         <head>
          <title>Error</title>
	     </head>
         <body>
          <h1>An unrecoverable error has occured.</h1>
          <h2>An email has been sent to our support team.</h2>
          <h2>We apologize for the inconvenience.</h2>
         </body>
        <html>
<?php
	}

	$errtype = errno_to_errtype($errno);
	$uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; # NOTE: This is a bit simplified.
	$remote_system = $_SERVER['REMOTE_ADDR'] . ', port ' . $_SERVER['REMOTE_PORT'];
	$date_time = date('Y-m-d H:i:s T');

	$error_message =  "\n";
	$error_message .= "Error number: $errno ($errtype)\n";
	$error_message .= "Error string: $errstr\n";
	$error_message .= "Error file: $errfile\n";
	$error_message .= "Error line: $errline\n\n";
	$error_message .= "URI: $uri\n";
	$error_message .= "Remote IP: $remote_system\n";
	$error_message .= "Date and time: $date_time\n\n";

	$error_message .= backtrace();
	$error_message .= "\n";
	$error_message .= dump_globals();
	$error_message .= "\n";

	# If we're in the DEV environment, dump the error message to the screen.
# TODO: Only print if not production.
#	if ( isset($_SERVER['ENVIRONMENT']) && 'DEV' == $_SERVER['ENVIRONMENT'] )
	{
		print '<div style="background:#FFAAAA; border:solid #FF0000 5px"><h3>PHP ERROR</h3><pre>';
		print ($error_message);
		print '</pre></div>';
	}

	# Email the error message to the admin(s).
	$subject = "An error occured on the XXXXX web site.";
	$headers = "From: {$EMAIL['from']}\r\n";
# TODO: Only send email if in production. (Or use a config setting.)
#	mail($EMAIL['report_errors_to'], $subject, $error_message, $headers);

	# If it's just a warning or a notice, let the program continue.
	if ( $errno == E_WARNING || $errno == E_NOTICE )
		return; # NOTE: If we returned false, default error handler would be run.
	else
		die();
}


function backtrace ()
{
	if ( !function_exists('debug_backtrace') )
		return '';

	$result = "Backtrace:\n";

	$backtrace = debug_backtrace();
	array_shift($backtrace); # Ignore the call to this 'backtrace()' function.
	array_shift($backtrace); # Ignore the call to the 'custom_error_handler()' function.

	foreach ( $backtrace as $i => $frame )
	{
		$result .= "[$i] in function {$frame['class']}{$frame['type']}{$frame['function']}";
		if ( $frame['file'] )
			$result .= " in {$frame['file']}";
		if ( $frame['line'] )
			$result .= " on line {$frame['line']}";
		$result .= "\n";
	}

	return $result;
}


# This function is a bit more verbose than it needs to be, but it's not worth it to clean it up.
function dump_globals ()
{
	# Don't print any of these global variables.
	$exclude_globals = array('GLOBALS', 'HTTP_POST_VARS', 'HTTP_GET_VARS', 'HTTP_COOKIE_VARS', 
			'HTTP_SESSION_VARS', 'HTTP_SERVER_VARS', 'HTTP_ENV_VARS', 'HTTP_POST_FILES',
			'_REQUEST', '_POST', '_GET', '_COOKIE', '_SESSION', '_SERVER', '_ENV', '_FILES'
	);

	$result = "Request Variables:\n";
	foreach ( $_REQUEST as $k => $v )
	{
		$v = var_export($v, true);
		$result .= '$_REQUEST[' . "'$k'] = $v\n";
	}

	$result .= "\nSession Variables:\n";
	foreach ( $_SESSION as $k => $v )
	{
		$v = var_export($v, true);
		$result .= '$_SESSION[' . "'$k'] = $v\n";
	}

	$result .= "\nGlobal Variables:\n";
	foreach ( $GLOBALS as $k => $v )
	{
		# Ignore top-level superglobals and other globals we want to exclude.
		if ( in_array($k, $exclude_globals) )
			continue;

		# Ignore variables that got auto-globaled.
		if ( //ini_get('register_globals') && 
					((isset($_REQUEST[$k]) && $_REQUEST[$k] == $v) || 
					 (isset($_SERVER[$k])  && $_SERVER[$k]  == $v) || 
					 (isset($_ENV[$k])     && $_ENV[$k]     == $v) ||
					 (isset($_SESSION[$k]) && $_SESSION[$k] == $v) ||
					 (isset($_COOKIE[$k])  && $_COOKIE[$k]  == $v) || 
					 (isset($_FILES[$k])   && $_FILES[$k]   == $v)) ) 
			continue;

		$v = var_export($v, true);
		$result .= '$' . "$k = $v\n";
	}

	return $result;
}


# Define some constants that may not be defined in older versions of PHP.
if ( !defined('E_STRICT') )            define('E_STRICT', 2048);
if ( !defined('E_RECOVERABLE_ERROR') ) define('E_RECOVERABLE_ERROR', 4096);

function errno_to_errtype ( $errno )
{
	# Note that some of these error types may not actually make it as far as running this code.
	switch($errno)
	{
		case E_ERROR:
			return "Error";
		case E_WARNING:
			return "Warning";
		case E_PARSE:
			return "Parse Error";
		case E_NOTICE:
			return "Notice";
		case E_CORE_ERROR:
			return "Core Error";
		case E_CORE_WARNING:
			return "Core Warning";
		case E_COMPILE_ERROR:
			return "Compile Error";
		case E_COMPILE_WARNING:
			return "Compile Warning";
		case E_USER_ERROR:
			return "User Error";
		case E_USER_WARNING:
			return "User Warning";
		case E_USER_NOTICE:
			return "User Notice";
		case E_STRICT:
			return "Strict Notice";
		case E_RECOVERABLE_ERROR:
			return "Recoverable Error";
		default:
			return "Unknown error type";
	}
}

?>
