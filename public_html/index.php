<?php

date_default_timezone_set('America/Bogota');
session_start();

ini_set('display_errors', true);
error_reporting(E_ALL);

//Library
require '../vendor/autoload.php';
require '../App/Config/config.php';

//Llamar al controlador indicado

if (empty($_GET['url'])) {
    $url = '';
} else {
    $url = $_GET['url'];
}
$request = new Request($url);
$request->execute();