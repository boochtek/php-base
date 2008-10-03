<?php

require_once '../framework/core.php';


$x = new MyModel();
$x->load(1);
$x['name'] = 'Foobar, Inc.';
$dump = $x->dump();
//$x->save();

render('example');
