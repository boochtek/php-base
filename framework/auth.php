<?php

/*
TODO:	See how I did this in my wiki code.
TODO: login/authentication/current_user stuff (framework/login?)
TODO: authorization stuff (framework/authz?)
		logged_in(), logged_in_as_admin()
		$current_user = current_user(); $current_user->is_in_group('admin')
*/

function logged_in ()
{
	return null != current_user_id();
}


function logged_in_as_admin ()
{
	return logged_in() && isset($_SESSION['is_admin']) && true == $_SESSION['is_admin'];
}


function current_user_id ()
{
	if ( !isset($_SESSION['user_id']) )
		return null;
	
	return $_SESSION['user_id'];
}


function current_user ()
{
	return new User(current_user_id());
}


function check_password ()
{
}


function log_in ( $username, $password )
{
	$user_id = User::find($username);
	if ( $user_id != null ) # TODO: Check password!!!!
	{
		session_start();
		$_SESSION = array();
		$_SESSION['user_id'] = $user_id;
		$_SESSION['is_admin'] = false;	# TODO: Set admin if necessary.
		return true;
	}
	return false;
}


function log_out ()
{
	# Set cookies to expire in the past, so the browser will delete them.
	#setcookie('user_id', '', time()-60);
	#setcookie(session_name(), '', time()-60);

	# Unset all session variables.
	$_SESSION = array();
	session_unset();

	# Destroy the session.
	session_destroy();
}

?>