<?php

require 'SplClassLoader.php';


$dsn = 'mysql:dbname=wowarmory;host=127.0.0.1;charset=utf8';
$user = 'root';
$password = 'root';
$self = __DIR__;


$classLoader = new SplClassLoader('WowArmory', $self . '/lib');
$classLoader->register();

ini_set('error_log', $self . '/php-cli-errors.txt');
ini_set('log_errors', true);
ini_set('display_errors', true);
ini_set('memory_limit', '256M');
error_reporting(-1);
set_time_limit(0);


try {
	$dbh = new PDO($dsn, $user, $password, [
		PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION
		, PDO::ATTR_EMULATE_PREPARES   => false
		, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	]);
} catch (PDOException $e) {
	echo 'Connection failed: ' . $e->getMessage();
	exit;
}

unset($dsn, $user, $password, $self);