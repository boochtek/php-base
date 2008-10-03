<?php

#require_once php_file('unit_tester', SIMPLETEST_DIR);
#require_once php_file('reporter', SIMPLETEST_DIR);
#require_once 'file_containing_code_under_test.php'

class TestSomething extends UnitTestCase
{
	
}

$reporter = from_cli() ? new TextReporter() : new HtmlReporter();
$test = new TestSomething();
$test->run($reporter);
