<?php

declare(strict_types=1);

include_once("./src/XboxAPI/XboxAPI.php");

$api = new XboxAPI();
$result = $api->login("emailaddress", "password");

var_dump($result);