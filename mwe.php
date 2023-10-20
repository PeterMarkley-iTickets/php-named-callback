<?php

function execute_func($arg, callable $func) {
	$func($arg);
}

$greet = function ($greeting) {
	echo $greeting . "\n";
};

execute_func("hello world", $greet);