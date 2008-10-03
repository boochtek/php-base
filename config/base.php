<?php

# Set of error types to ignore in custom error handler. Use '|' to specify multiple levels (i.e. E_NOTICE|E_WARNING).
# Set this to E_NOTICE if you've got legacy code that uses variables before they've been set.
$CONFIG['ignore_errors'] = 0;

# Emails will be sent to this address whenever an error is encountered.
# NOTE: You may specify multiple comma-separated addresses .
$EMAIL['report_errors_to'] = 'craig@buchek.com';

# TODO: This should go in config/email.php file. Should the previous one too?
# All emails sent via the framework will come from this address.
$EMAIL['from'] = 'Ideal Simple PHP Framework (Do Not Reply) <server@boochtek.com>';


?>
