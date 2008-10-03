<?php


## TODO: Other INPUT field types.
## TODO: Multiple error messages per field.
## TODO: Split validation into a separate class hierarchy from fields. Pass validator to field constructor.
## TODO: Pass some of the options along to the INPUT element.
## TODO: Integrate into the framework. How do we integrate with model validations? new ModelField(model, name)?
## TODO: Security - need to filter input better, to prevent SQL injections, XSS, CSRF and such.
## TODO: Confirmation - password and password_confirmation fields have to match each other.
## TODO: Use a token to prevent CSRF attacks.


## Requirements:
##	Works well with designers who want to lay out all the fields manually, using DreamWeaver et al.
##	Also works without designers, where the form can lay out its own fields via PHP calls.
##	Definition of fields is mostly declarative.
##	Validates all fields.
##	Generates error messages for each field.
##	Clean code.


error_reporting(E_ALL | E_STRICT);


# Generate OPTION tags to put in a SELECT element. The options may be strings, or arrays of (label, value).
function options_for_select ( /*array*/ $options, /*string*/ $selected_option = NULL )
{
	$result = '';
	foreach ( $options as $option )
	{
		if ( is_array($option) )
		{
			$label = $option[0];
			$value = $option[1];
		}
		else
		{
			$label = $option;
			$value = $option;
		}
		$selected = (0 == strcasecmp($selected_option, $label)) || (0 == strcasecmp($selected_option, $value)) ? "selected='selected'" : '';
		$result .= "<option value='$value' $selected>$label</option>\n";
	}
	return $result;
}

function indexed_array ( $a )
{
	$result = array();
	$index = 0;
	foreach ( $a as $item )
	{
		$result[$index] = array($item, $index);
		$index++;
	}
	return $result;
}

function select_state ( $name, /*string*/ $selected_state = 'Missouri' )
{
	$states = get_list_of_states();
	$state_options = options_for_select($states, $selected_state);
	return "<select id='$name' name='$name'>$state_options</select>";
}

# NOTE: $selected date is a string in YYYY-MM-DD format.
function select_date ( $year_name, $month_name, $day_name, $min_year, $max_year, $selected_date = NULL )
{
	$selected = explode('-', $selected_date);

	$years = array_merge(array('Select Year'), range($min_year, $max_year));
	$months = indexed_array(array('Select Month', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'));
	$days = array_merge(array('Select Day'), range(1, 31));

	$year_options = options_for_select($years, $selected[0]);
	$month_options = options_for_select($months, $selected[1]);
	$day_options = options_for_select($days, $selected[2]);

	return "<select id='$year_name' name='$year_name'>$year_options</select>"
	     . "<select id='$month_name' name='$month_name'>$month_options</select>"
	     . "<select id='$day_name' name='$day_name'>$day_options</select>";
}

function select_ssn ( $name = 'ssn', $selected_ssn = NULL )
{
	$ssn = explode('-', $selected_ssn);
	return " <input name='{$name}1' type='text' id='{$name}1' value='{$ssn[0]}' size='3' maxlength='3' />"
	     . "-<input name='{$name}2' type='text' id='{$name}2' value='{$ssn[1]}' size='2' maxlength='2' />"
	     . "-<input name='{$name}3' type='text' id='{$name}3' value='{$ssn[2]}' size='4' maxlength='4' />";
}

function flag_if_invalid ( $test )
{
	if ( !$test )
		return '';
		
	return '<font color="red">* </font>';
}

function flag_as_required ()
{
	return '<span class="tools-adminHistoryText"><strong>*</strong></span>';
}


class Field implements ArrayAccess
{
	const TEXT_FILTER = '/[^a-zA-Z0-9 !\/@#$%^&*()+?.-]/';
	const NAME_FILTER = '/[^a-zA-Z ,\.\'-]/';
	const FILENAME_FILTER = '/[^a-zA-Z0-9.@()-]/';
	const IDENTIFIER_FILTER = '/[^a-zA-Z0-9-]/';
	const URL_FILTER = '/[^a-zA-Z0-9.@:,;?&#$\/-]/';
	const INTEGER_FILTER = '/[^0-9]/';
	const FLOAT_FILTER = '/[^0-9\.]/';

	const ZIPCODE_REGEX = '/^\d\d\d\d\d(-\d\d\d\d)?$/';

	protected $name;
	protected $value;
	protected $required;
	protected $valid = NULL;
	protected $options = array();

	public function __construct($name, $required = true, array $options = array())
	{
		$this->name = $name;
		$this->required = $required;
		$this->options = $options;
		$this->value = $this->filter(@$_POST[$name]); # NOTE: filter() requires options to be set. NOTE: Defaults to NULL, without causing errors if key doesn't exist.
	}

	public function __toString()
	{
		return $this->value;
	}

	public function name()
	{
		return $this->name;
	}

	protected function filter($item)
	{
		if ( empty($item) )
			return $item;

		if ( !empty($this->options['filter']) )
			return preg_replace($this->options['filter'], '_', $item);
		else
			return $item;
	}

	public final function is_valid()
	{
		# Short-circuit if we've cached the validity.
		if ( $this->valid != NULL )
			return $this->valid;

		if ( !$this->required && empty($this->value) )
			$this->valid = true;
		elseif ( $this->required && empty($this->value) )
			$this->valid = false;
		else
			$this->valid = $this->validate();

		return $this->valid;
	}

	protected function validate()
	{
		$value = $this->value;
		$options = $this->options;

		//validate_length();
		if ( !empty($options['length']) && (strlen($value) < $options['length'][0] || strlen($value) > $options['length'][1] ) )
			return false;

		if ( !empty($options['validate']) )
		{
			$validation_function = $options['validate'];
			if ( !function_exists($validation_function) || $validation_function($value) == false )
				return false;
		}

		//validate_regex();
		$regex = $this->regex();
		if ( !empty($regex) && !preg_match($regex, $value) )
			return false;
		$regex = @$options['match'];
		if ( !empty($regex) && !preg_match($regex, $value) )
			return false;

		return true;
	}

	protected function regex()
	{
		return NULL;
	}

	public function error_message()
	{
		if ( $this->is_valid() )
			return '';
		if ( !empty($this->options['message']) )
			return $this->options['message'];
		return $this->default_error_message();
	}

	protected function default_error_message()
	{
		return "{$this->label()} is not valid";
	}

	public function label()
	{
		if ( !empty($this->options['label']) )
			return $this->options['label'];
		return ucwords(strtr($this->name, '_', ' '));
	}

	public function input_field()
	{
		$value = htmlentities($this->value, ENT_QUOTES); # TODO: Not sure if this is what I want, or to escape quotes.
		return "<input type='text' id='{$this->name}' name='{$this->name}' value='{$value}' />";
	}

	public function input_field_with_label()
	{
		return "<label for='{$this->name}' class='{$this['valid']} {$this['required']}'>\n"
			. "  {$this['label']}: {$this->input_field()}\n"
			. "</label>\n";
	}

	# These implement array accessors in PHP 5, so our class acts like an array.
	public function offsetExists($prop)
	{
		return true; # TODO: Only return true if it's something we actually handle.
	}
	public function offsetGet($prop)
	{
		switch ($prop)
		{
		case 'is_valid':
			return $this->is_valid();
		case 'valid':
			return $this->is_valid() ? 'valid' : 'invalid';
		case 'is_required':
			return $this->required;
		case 'required':
			return $this->required ? 'required' : '';
		case 'label':
			return $this->label();
		case 'error':
			return $this->error_message();
		default:
			return NULL;
		}
	}
	public function offsetSet($prop, $value)
	{
		# Cannot set any properties.
	}
	public function offsetUnset($prop)
	{
		# Cannot unset any properties.
	}
}


class Confirmation extends Field
{
	protected $field1;
	protected $field2;

	public function __construct($field1_name, $field2_name)
	{
		$this->field1 = $field1_name;	# TODO: Need to get the actual items from the form.
		$this->field2 = $field2_name;
	}

	public function validate()
	{
		return false;
		//return ($this->field1 == $this->field2);
	}

	public function error_message()
	{
		return 'Items do not match';
	}

	public function input_field()
	{
		return '';
	}

	public function input_field_with_label()
	{
		return '';
	}
}


class EmailField extends Field
{
	public function validate()
	{
		if ( !parent::validate() )
			return false;

		# NOTE: This does NOT check to see that the domain is valid, or that the email address can receive emails.
		# Got parts of this from http://phpsec.org/projects/guide/1.html#1.4.3 (CMB 2007-07-12)
		# Got list of disallowed punctuation and control characters from http://tfletcher.com/lib/rfc822.rb (CMB 2007-07-12)
		$regex = '/^
					[^\s@\x00-\x20"(),:;<>\x5b-\x5d\x7f-\xff]+	# at least 1 character: no spaces, @-signs, control-characters, most punctuation (including [\]), or non-ASCII characters
					@					# require the @ sign
					([-a-z0-9]+\.)+		# domain name must consist of alpha-numeric characters, dots, and dashes
					[a-z]{2,6}			# top-level domain must consist of at least 2 characters, and no more than 6 (museum and travel TLDs)
				$/xi';					# x = allow extended syntax, i = case insensitive
		return preg_match($regex, trim($this->value));
	}

	protected function default_error_message()
	{
		return "{$this->label()} must be a valid email address";
	}
}


class PhoneNumberField extends Field
{
	protected function regex()
	{
		# NOTE: This allows only US phone numbers, with area code required, and optional extension.
		# Got parts of this from http://www.onlamp.com/pub/a/onlamp/2003/08/21/regexp.html example code. (CMB 2007-06-21)
		return '/^
					\(?					# allow optional open parentheses
					[2-9]\d\d			# require 3-digit area code (1st digit must be 2-9)
					[)-.\s]				# require separator -- either a close parentheses, a dash, a space, or a period
					\s*					# allow more spaces
					[2-9]\d\d			# require 3-digit exchange number (1st digit must be 2-9)
					[-\s.]				# require another separator
					\d{4}				# require 4-digit station number
					(					# group the extension
					,?\s*				# allow a comma and spaces
					(x|ext|extension)	# allow a couple ways to specify the extension
					\.?\s*				# allow a period and spaces
					\d{1,5}				# extension can be 1-5 digits
					)?					# extension is optional
				$/xi';					# x = allow extended syntax, i = case insensitive
	}

	protected function default_error_message()
	{
		return "{$this->label()} must be a valid US phone number, including area code";
	}
}


class ZipCodeField extends Field
{
	protected function regex()
	{
		# This allows US ZIP codes, either 5-digit or 5+4 (separated by a dash).
		return '/^
					\d\d\d\d\d			# require 5-digit zip code
					(-\d\d\d\d)?		# allow optional 4-digits preceded by a dash
				$/x';					# x = allow extended syntax
	}
}


class PasswordField extends Field
{
	protected function validate()
	{
		return false;	# TODO: Require upper, lower, digits, and special characters.
	}
	public function input_field()
	{
		$value = htmlentities($this->value, ENT_QUOTES); # TODO: Not sure if this is what I want, or to escape quotes.
		return "<input type='password' id='{$this->name}' name='{$this->name}' value='{$value}' />";
	}
}


# NOTE: 2nd argument to constructor is different -- it's the value of the hidden field, instead of a boolean telling if it's a required field.
class HiddenField extends Field
{
	public function __construct($name, $value = '', array $options = array())
	{
		parent::__construct($name, true, $options);
		$this->value = $value;
		# TODO: Should probably check that any passed-in $_POST is correct, and throw an exception if not.
	}

	public function validate()
	{
		return true; # Never show an error on a hidden field.
	}

	public function input_field()
	{
		$value = htmlentities($this->value, ENT_QUOTES); # TODO: Not sure if this is what I want, or to escape quotes.
		return "<input type='hidden' id='{$this->name}' name='{$this->name}' value='{$value}' />";
	}

	public function input_field_with_label()
	{
		return "{$this->input_field()}\n";
	}
}


class Form implements ArrayAccess
{
	public $fields = array(); ## TODO: Should be private?

	public function __construct(array $field_definitions)
	{
		# NOTE: This sets the array indexes to the names of the fields.
		foreach ( $field_definitions as $def )
		{
			$this->fields[$def->name()] = $def;
		}
	}

	# Validation functionality.
	public function is_valid()
	{
		foreach ( $this->fields as $field )
		{
			if ( !$field->is_valid() )
				return false;
		}
		return true;
	}
	public function build()
	{
		$result = '';
		foreach ( $this->fields as $field )
		{
			$result .= $field->input_field_with_label();
		}
		return $result;
	}
	public function errors() # Returns array of error messages.
	{
		$result = array();
		foreach ( $this->fields as $field )
		{
			$error_message = $field->error_message();
			if ( '' != $error_message )
				array_push($result, $error_message);
		}
		return $result;
	}
	public function errors_as_html() # Returns error messages in a block of HTML.
	{
		ob_start();
		?>
		<ul id="errors">
		  <?php foreach ( $this->errors() as $error ): ?>
		  <li><?php echo $error; ?></li>
		  <?php endforeach; ?>
		</ul>
		<?
		return ob_get_clean();
    }

	# These implement array accessors in PHP 5, so our class acts like an array.
	# TODO: Return '' or NULL if property doesn't exist. (I think this is the case already.)
	public function offsetExists($prop)
	{
		return array_key_exists($prop, $this->fields);
	}
	public function offsetGet($prop)
	{
		return $this->fields[$prop];
	}
	public function offsetSet($prop, $value)
	{
		# Cannot set any properties.
	}
	public function offsetUnset($prop)
	{
		# Cannot unset any properties.
	}
}


# Testing the API.

$_POST['name'] = 'Mr. Robert Piñero J. O\'Leary-Smith, Sr.';
$_POST['dog'] = 'not dog';
$_POST['email_address'] = 'a@a.c';
$_POST['phone_number'] = '800-921-DOGS x 128';
$_POST['zip_code'] = '123';
$_POST['zip_code_2'] = '12345-123';
$_POST['password'] = '12345';

$FORM = new Form(array(
	new Field('name', true, array('filter' => Field::NAME_FILTER)),
	new Field('dog', true, array('match' => '/^dog$/', 'message' => 'Dog field must be "dog"')),
	new EmailField('email_address', false),
	new PhoneNumberField('phone_number', true),
	new ZipCodeField('zip_code', true, array('label' => 'ZIP Code')),
	new Field('zip_code_2', true, array('match' => Field::ZIPCODE_REGEX, 'label' => 'ZIP Code #2')),
	new PasswordField('password', true, array('length' => array(5,10), 'message' => 'Password must be 5-10 characters long')),
	new Confirmation('zip_code', 'zip_code_2'),
	new HiddenField('hidden_field', 'hidden_value'),
));


/*
Test API with separate validators and fields.
$FORM = new Form(array(
	new Field('name', true, NULL, array('filter' => Field::NAME_FILTER)),
	new Field('dog', true, '/^dog$/', array('message' => 'Dog field must be "dog"')),
	new Field('email_address', false, new EmailValidator()),
	new Field('phone_number', true, new PhoneNumberValidator()),
	new Field('zip_code', true, new ZipCodeValidator(), array('label' => 'ZIP Code')),
	new Field('zip_code_2', true, Field::ZIPCODE_REGEX, array('label' => 'ZIP Code #2')),
	new PasswordField('password', true, new Validator(array('length' => array(5,10), 'message' => 'Password must be 5-10 characters long'))),
	new ConfirmationValidator('zip_code', 'zip_code_2'),
	new HiddenField('hidden_field', 'hidden_value'),
));

*/


/*
types: integer, number, time, date, date_time, text_area, ip_address, country, state, checkbox, submit (TODO)
match => regex that the field value has to match # TODO: rename as regex?
length => array(min, max) -- length of input must be between the 2 values
message => message to print if field is invalid
label => text of the label
title, alt, disabled, class, readonly, accesskey (TODO)
maxlength, size (INPUT) (TODO)
rows, cols, tab (TEXTAREA) (TODO)
multiple (SELECT) (TODO)
repeatable => default false -- add a JS button to add multiples of this field (TODO)
*/


//var_dump($FORM->fields);

if ( $FORM->is_valid() )
{
	$my_object = array(); //$my_object = new MyObject();
	$my_object['name'] = $FORM['name'];
	$my_object['email'] = $FORM['email_address'];
	echo "redirect_to('thank-you.php')\n";
	exit();
}

?>

<html>
  <head>
    <title>Testing form validation and building</title>
  </head>

  <body>

<? if ( !$FORM->is_valid() ): ?>
    <ul id="errors">
  <? foreach ( $FORM->errors() as $error ): ?>
      <li><?= $error ?></li>
  <? endforeach; ?>
    </ul>
<? endif; ?>

    <?= $FORM->errors_as_html() ?>

    <form action="#" method="POST">
      <label class="<?= $FORM['name']['valid'] ?> <?= $FORM['name']['required'] ?>">
        <?= $FORM['name']['label'] ?>: <input type="text" id="name" name="name" value="<?= $FORM['name'] ?>"  />
      </label>
      <label class="<?= $FORM['email_address']['valid'] ?> <?= $FORM['email_address']['required'] ?>">
        <?= $FORM['email_address']['label'] ?>: <input type="text" id="email_address" name="email_address" value="<?= $FORM['email_address'] ?>" />
      </label>

      <?= $FORM->build() # everything INSIDE the FORM element ?>

    </form>

  </body>
</html>
