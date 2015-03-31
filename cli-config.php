<?php


/**
 * config used for most php scripts in this project.
 */

ini_set('error_log', 'php-cli-errors.txt');
ini_set('log_errors', true);
ini_set('display_errors', true);
error_reporting(-1);//-1 is max
spl_autoload_register();
require 'SplClassLoader.php';
$classLoader = new SplClassLoader('WowArmory', 'lib/');
$classLoader->register();
$dsn = 'mysql:dbname=wowarmory;host=127.0.0.1;charset=utf8';
$user = 'root';
$password = 'root';

try {
	$dbh = new PDO($dsn, $user, $password, [
		PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES => false
	]);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
}

unset($dsn, $user, $password, $classLoader);