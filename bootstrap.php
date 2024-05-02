<?php
// bootstrap.php
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use App\Entity\Movie; // Import the Movie entity


// require_once __DIR__ . '/vendor/autoload.php';
// require dirname(__DIR__) . '/vendor/autoload.php';
// require_once __DIR__ . "/vendor/autoload.php";

require_once "vendor/autoload.php";

// require dirname(__DIR__) . '/vendor/autoload.php';



// Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: array(__DIR__ . "/src"),
    isDevMode: true,
);
// or if you prefer XML
// $config = ORMSetup::createXMLMetadataConfiguration(
//    paths: array(__DIR__."/config/xml"),
//    isDevMode: true,
//);

// configuring the database connection
// configuring the database connection for MySQL
$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'dbname' => 'db',
    'user' => 'root',
    'password' => 'root',
    'host' => '127.0.0.1',
    'port' => '50000',
], $config);


// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);
