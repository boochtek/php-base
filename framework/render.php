<?php


## TODO: Is there a way to simplify this? Because the typical call path looks like this:
##			render -> render_file_with_layout -> render_to_string -> render -> render_file_with_no_layout
## TODO: Allow setting title and other options in the HTML header section.
## TODO: Determine what to pass to layout -- filename to include or string to echo. (Both work fine.)
##			Probably pass it a string named $CONTENT.
## TODO: Default view_name to the name of the controller.
## TODO: See if we can automatically call render() if the controller did not call any render() function.


# NOTE: Can pass '' or NULL for no layout. If $view_name is not specified, it is extracted from the controller location.
function render ( $view_name='', $layout='default', $extra_vars=null )
{
    if ( '' == $view_name || null == $view_name )
    {
        # TODO: Determine the default view to use for the controller we were called from.
    }

    # Find the view file. Return silently if not found.
    $view_file = php_file($view_name, VIEWS_DIR);
    if ( null == $view_file ) { return; }

    # Find the layout file.
    $layout_file = php_file($layout, LAYOUTS_DIR);

    # Render the view_file, with or without the layout.
    if ( null == $layout_file )
    {
        render_file_with_no_layout($view_file, $extra_vars);
    }
    else
    {
        render_file_with_layout($view_file, $layout_file, $extra_vars);
    }
}


# NOTE: Layout defaults to no layout in this function. Also, the $view_name must be specified.
function render_to_string ( $view_name, $layout='', $extra_vars=null )
{
	## FIXME: NEED TO HANDLE GLOBALS.

    # Set up output buffering.
	ob_start();

    # Call render.
    render($view_name, $layout, $extra_vars);

    # Return the buffer contents.
	return ob_get_clean();
}


function render_partial ( $partial_name, $extra_vars=null )
{
    # Partials are stored in their own directory.
    $view_name = 'partials/' . $partial_name;

    # Call render.
    render($view_name, '', $extra_vars);
}


$EXCLUDE_GLOBALS = array('GLOBALS', 'HTTP_POST_VARS', 'HTTP_GET_VARS', 'HTTP_COOKIE_VARS', 
		'HTTP_SESSION_VARS', 'HTTP_SERVER_VARS', 'HTTP_ENV_VARS', 'HTTP_POST_FILES',
		'_REQUEST', '_POST', '_GET', '_COOKIE', '_SESSION', '_SERVER', '_ENV', '_FILES',
		'EXCLUDE_GLOBALS'
);


function render_file_with_layout ( $view_file, $layout_file, $extra_vars=null )
{
    # Set global variables.
	global $EXCLUDE_GLOBALS;
	foreach ( $GLOBALS as $k => $v )
	{
		# Ignore top-level superglobals and other globals we want to exclude.
		if ( !in_array($k, $EXCLUDE_GLOBALS) )
			global $$k;
	}

    # Pass the layout the content of the rendered view that it needs to wrap.
    global $VIEW, $VIEW_FILE;
    $VIEW_FILE = $view_file;
    $VIEW = render_to_string($view_file, '', $extra_vars); ## TODO: RENAME TO $CONTENT?
    # TODO: Add a line-feed to end, if the view does not end with one.

    # Have the layout render itself.
    include($layout_file);
}


function render_file_with_no_layout ( $view_file, $extra_vars=null )
{
    # Set global variables.
	global $EXCLUDE_GLOBALS;
	foreach ( $GLOBALS as $k => $v )
	{
		# Ignore top-level superglobals and other globals we want to exclude.
		if ( !in_array($k, $EXCLUDE_GLOBALS) )
			global $$k;
	}

    # Have the view render itself.
    include($view_file);
}



function render_email ( $email_message_name )
{
	# TODO: Should we also deliver the emails here?
	return render_to_string( "emails/$email_message_name" );
}
