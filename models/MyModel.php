<?php


class MyModel extends BaseModel
{
	public function dump()
	{
		ob_start();
		var_dump($this->properties);
		return ob_get_clean();
	}
}

