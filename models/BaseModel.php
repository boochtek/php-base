<?php

require_once php_file('database', FRAMEWORK_DIR);


## TODO: Determine table name from class name.
## TODO: Use info on columns during loads/saves.
## TODO: Allow sub-classes to define custom properties that aren't in the database (and not stored in $properties).
##			Have getter/setter methods accessible via the array syntax.
##			Have a generic getter/setter that uses the properties array.s
## TODO: Test save() with an object with no ID (i.e. a newly created object, not loaded from the database).
## TODO: Test delete(). What do we do after deletion.
## TODO: Provide some assistance for associations.
## TODO: Find functions.

abstract class BaseModel implements ArrayAccess
{
	# ID of object in database. NOTE: Most likely duplicated in $this->properties['id'].
	protected $id = NULL;

	# Data fields from the database.
	protected $properties;

	# Database table to get items from.
	protected $table_name = NULL;
	protected $table = NULL;

	# Errors from validations
	protected $errors = array();

	public function __construct ( $id = NULL )
	{
		# TODO: Find default table name from name of the class, pluralized and lower-cased.
		if ( NULL == $this->table_name )
			$this->table_name = NULL;

		# Instantiate a table object.
		$this->table = new DBTable($this->table_name);

		# If we were passed an ID, load it into the object.
		if ( NULL != $id )
			$this->load($id);
	}

	# Basic CRUD functionality.
	public function load ( $id )
	{
		# TODO: Should see if $this->id is already set. If so, raise an error, unless FORCE option is passed.

		$this->properties = $this->table->load_row($id);
		$this->id = $id;

		# TODO: Coerce properties into proper types, per database info.
		foreach ( $this->table->column_info() as $field => $info )
		{
#			$field = 'id',
#			$info['Type'] => 'int(11)',
#			$info['Null'] => 'NO',
#			$info['Key'] => 'PRI',
#			$info['Default'] => NULL,
#			$info['Extra'] => 'auto_increment',
		}
	}

	public function save ()
	{
		# TODO: Make sure this works if there's no ID (i.e. it hasn't been saved to the database yet).
		# TODO: Make sure all the properties match up with fields in the table.
		if ( $this->id )
		{
			$this->table->update_row($this->id, $this->properties);
		}
		else
		{
			$this->id = $this->table->insert_row($this->properties);
		}
	}

	public function delete()
	{
		$this->table->delete_row($this->id);
	}

	# TODO: Validation functionality.
	public function is_valid()
	{}
    public function errors() # Returns array of error messages, after calling is_valid() or save().
    {}

	# These implement array accessors in PHP 5, so our class acts like an array.
	# TODO: Implement these as get()/set() for PHP 4, if necessary.
	# TODO: Check that the model supports the fields requested.
	public function offsetExists($prop) { return array_key_exists($prop, $this->properties); }
	public function offsetGet($prop) { return $this->properties[$prop]; }
	public function offsetSet($prop, $value) { $this->properties[$prop] = $value; }
	public function offsetUnset($prop) { unset($this->properties[$prop]); }

	# Finders.
	public static function find($where)
    {}


	# TODO: These would be nice to have.
    public function from_array($a)
    {
    	// From OnLAMP "Understanding MVC in PHP", but this is not for our method of using $properties.
		$valid = get_class_vars(get_class($this));
        foreach ( $valid as $var => $val )
        {
            if ( isset($a[$var]) )
            {
                $this->$var = $a[$var]; // NOTE: That's not $this->var!
            }
        }
    }
    public function to_array()
    {
    	 // From OnLAMP "Understanding MVC in PHP", but this is not for our method of using $properties.
        $me = new ReflectionClass($this);
        $defaults = $me->getDefaultProperties();
        $return = array();
        foreach ( $defaults as $var => $val )
        {
            if ( $this->$var instanceof BaseModel )
            {
                $return[$var] = $this->$var->to_array();
            }
            else
            {
                $return[$var] = $this->$var;
            }
        }
        return $return;
    }
}


