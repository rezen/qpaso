<?php

error_reporting(-1);

require 'vendor/autoload.php';


$query = "map(href=(attr name | first) data=json_encode, text=`a`)";
$data = [(object) ['name' => 'Doe']];

$resolved = \Qpaso\query($query, $data);
print_r($resolved );