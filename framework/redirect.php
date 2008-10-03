<?php


# If this file is invoked directly, run some unit tests.
if ( realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']) )
{
	unit_test_absolute_url();
	unit_test_canonical_url();
	exit;
}


function redirect_to ( $url )
{
    # Make sure URL is absolute. Required to be absolute by section 14.30 of RFC 2616.
    $url = absolute_url($url);

    # Output headers telling the browser to redirect to the desired URL.
    # NOTE: Should actually use a 303 return code, but most browsers don't handle that.
    #       See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for details.
    header('HTTP/1.1 302 Moved Temporarily');
    header('Status: 302 Moved Temporarily');
    header("Location: $url");

    # We don't need to continue running any more PHP code, so exit.
    # Just in case the browser doesn't understand the headers we passed, send the new URL in the body.
    exit("Go to <a href='$url'>next page</a>.");
}


function https_required ()
{
	# Return if we're already using HTTPS.
	if ( isset($_SERVER['HTTPS']) )
		return;

	# Get the current URL, except using HTTPS instead of HTTP.
	#$url = current_url();
	#$url = preg_replace('/^http:/i', 'https:', $url);
	$url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

    # Output headers telling the browser to redirect to the desired URL.
    header('HTTP/1.1 301 Moved Permanently');
    header('Status: 301 Moved Permanently');
    header("Location: $url");

    # We don't need to continue running any more PHP code, so exit.
    # Just in case the browser doesn't understand the headers we passed, send the new URL in the body.
    exit("This page is only accessible via HTTPS. <a href='$url'>Click here to go to the HTTPS page</a>.");
}


function absolute_url ( $url, $base_url = null )
# Converts any URL to an absolute URL. If $base_url is not supplied, it will be determined by PHP.
# NOTE: Doesn't handle mal-formed URLs well, especially incorrect schemes.
# NOTE: Probably doesn't handle fragment identifiers (#sec3) well.
# TODO: Make sure this covers every case suggested by the algorithm in RFC 2396, section 5.2, step 6.
{
    # Get components from base URL or current URL, if base URL was not supplied.
    if ( empty($base_url) )
        $base_url = current_url();

    # If URL doesn't start with http: or https:, we'll need to add the base scheme and server.
    if ( !preg_match('/https?:/', $url) )
    {
        # Split the URL into some manageable pieces.
        $base_scheme = strtok($base_url, ':'); # Part before the first colon (:).
        $base_parts = explode('/', $base_url, 4); # Split the URL into no more than 4 pieces.
        $base_server = $base_parts[2]; # Part between first 2 slashes (//) and next slash (/).
        $base_path = '/' . $base_parts[3]; # Part after the 3rd slash (/).

        # If URL doesn't start with a /, it's relative to the current URL, so add the current path.
        if ( $url{0} != '/' )
        {
            # Remove anything after the first question mark (?), which is the query string.
            if ( strpos($base_path, '?') )
                $base_path = substr($base_path, 0, strpos($base_path, '?'));

            # Remove last part of $base_path to get the relative path from the top.
            $base_path = dirname($base_path);

            # Ensure that $base_path ends with a slash (/) so we can add on to the end.
            if ( $base_path{strlen($base_path)-1} != '/' )
                $base_path = $base_path . '/';

            # Add the relative URL to the base URL's path.
            $url = $base_path . $url;
        }

        # Add the scheme (protocol) and server to the front of the URL if it didn't include that.
        $url = "$base_scheme://$base_server" . $url;
    }

    return canonical_url($url);
}


function current_url ()
# Returns the URL currently being processed.
# NOTE: This returns the URL the client requested, before any Apache URL rewriting.
{
    # Determine if we're being accessed via HTTP or HTTPS.
    $base_scheme = (isset($_SERVER['HTTPS']) && 'on' == strtolower($_SERVER['HTTPS'])) ? 'https' : 'http';

    # Check what name we're being accessed as.
	# NOTE: Use HTTP_HOST here, which returns the hostname that the user accessed. SERVER_NAME returns the canonical hostname, which is not what we want.
	$base_server = $_SERVER['HTTP_HOST']; 

    # Check to see if we're on a non-standard port.
    if ( (80 != $_SERVER['SERVER_PORT'] && 'http' == $base_scheme) || (443 != $_SERVER['SERVER_PORT'] && 'https' == $base_scheme) )
        $base_server .= ':' . $_SERVER['SERVER_PORT'];

    # Get the URL relative to the server's root.
    # NOTE: REQUEST_URI gives URL before Apache URL rewriting; PHP_SELF gives the URL after rewriting.
    $base_path = $_SERVER['REQUEST_URI'];

    $url = "$base_scheme://$base_server" . $base_path;

    # If there's a query string, tack it onto the end.
    if ( !empty($_SERVER['QUERY_STRING']) )
        $url .= '?' . $_SERVER['QUERY_STRING'];

    return $url;
}


function canonical_url ( $url )
# Convert given URL to canonical form, removing dot (.) directories, dot-dot (..) directories, and duplicate slashes (//).
{
    $parts = explode('/', $url, 4);
    $path = '/' . $parts[3]; # Part after the 3rd slash (/).

    # Remove . and .. and duplicate / from path elements.
    for ( $i = 5; $i >= 0 ; $i-- )
        $path = str_replace('//', '/', $path);

    # Remove . and .. and duplicate / from path elements.
    while ( strstr($path, '/./') )
        $path = str_replace('/./', '/', $path);

    # Remove . and .. and duplicate / from path elements.
    while ( strstr($path, '/../') )
        $path = preg_replace('@/.*/\.\./@', '/', $path);

    return $parts[0] . '/' . $parts[1] . '/' . $parts[2] . $path;
}


function unit_test_absolute_url ()
{
	# Array of arrays containing URL, base URL, and expected result.
	$urls = array(
		array('http://buchek.com/xyz.html', null, 'http://buchek.com/xyz.html'),
		array('https://buchek.com/xyz.html', null, 'https://buchek.com/xyz.html'),

		array('/xyz.html', 'http://buchek.com/',  'http://buchek.com/xyz.html'),
		array('/xyz.html', 'http://buchek.com',   'http://buchek.com/xyz.html'),
		array('xyz.html', 'http://buchek.com/',   'http://buchek.com/xyz.html'),
		array('xyz.html', 'http://buchek.com',    'http://buchek.com/xyz.html'),

		array('/xyz.html', 'http://buchek.com/a/b/', 'http://buchek.com/xyz.html'),      # Is this right?
		array('/xyz.html', 'http://buchek.com/a/b', 'http://buchek.com/xyz.html'),
		array('xyz.html', 'http://buchek.com/a/b/', 'http://buchek.com/a/xyz.html'),     # Is this right?
		array('xyz.html', 'http://buchek.com/a/b', 'http://buchek.com/a/xyz.html'),

		array('x/xyz.html', 'http://buchek.com/a/b/', 'http://buchek.com/a/x/xyz.html'), # Is this right?

		array('../xyz.html', 'http://buchek.com/a/b', 'http://buchek.com/xyz.html'),
		array('./xyz.html', 'http://buchek.com/a/b/', 'http://buchek.com/a/xyz.html'),   # Is this right?
		array('./././xyz.html', 'http://buchek.com/a/b', 'http://buchek.com/a/xyz.html'),

		array('xyz.html', 'http://buchek.com/abc.html?xyz=42&abc=12', 'http://buchek.com/xyz.html'),
		array('xyz.html', 'http://buchek.com/a/b?xyz=42&abc=12', 'http://buchek.com/a/xyz.html'),
	);

	$test_count = sizeof($urls);
	$fail_count = 0;

	foreach ( $urls as $u )
	{
		$url = $u[0];
		$base_url = $u[1];
		$expected = $u[2];
		$result = absolute_url($url, $base_url);
		if ( $result != $expected )
		{
			echo "FAILED: absolute_url($url, $base_url) should result in $expected but returned $result\n";
			$fail_count++;
		}
	}

	if ( 0 == $fail_count )
		echo "All $test_count absolute_url tests PASSED!\n";
	else
		echo "FAILED tests - $fail_count of $test_count tests FAILED!\n";
}


function unit_test_canonical_url ()
{
	# Array of arrays containing URL, canonical URL.
	$urls = array(
		array('http://buchek.com/xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com//xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com///xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com////xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/////xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com//a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com///a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com////a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/////a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/a/xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/a//xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/a///xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/a////xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/a/////xyz.html', 'http://buchek.com/a/xyz.html'),
		array('http://buchek.com/./xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/./././xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/././././xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/./././././xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/x/../xyz.html', 'http://buchek.com/xyz.html'),
		array('http://buchek.com/x/y/../../xyz.html', 'http://buchek.com/xyz.html'),
	);

	$test_count = sizeof($urls);
	$fail_count = 0;

	foreach ( $urls as $u )
	{
		$url = $u[0];
		$expected = $u[1];
		$result = canonical_url($url);
		if ( $result != $expected )
		{
			echo "FAILED: canonical_url($url) should result in $expected but returned $result\n";
			$fail_count++;
		}
	}

	if ( 0 == $fail_count )
		echo "All $test_count canonical_url tests PASSED!\n";
	else
		echo "FAILED tests - $fail_count of $test_count tests FAILED!\n";
}


