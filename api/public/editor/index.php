<?php 

function adminer_object() {

  foreach (glob("plugins/*.php") as $filename) {
      include_once "./$filename";
  }
  
  $plugins = array(
    new AdminerFileUpload("images/"),
    //new CustomDatabase(),
  );

  return new AdminerPlugin($plugins); 
}

date_default_timezone_set("EST");

include_once __DIR__ . "/../../vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../../');
$dotenv->load();

include_once __DIR__ . '/editor.php';