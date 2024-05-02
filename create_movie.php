<?php
// create_product.php <name>
require_once "bootstrap.php";
require_once "src/Movie.php"; // Include the movie.php file

$newMovieTitle = $argv[1];

$movie = new Movie();
$movie->setTitle($newMovieTitle);

$entityManager->persist($movie);
$entityManager->flush();

echo "Created Product with ID " . $movie->getId() . "\n";
