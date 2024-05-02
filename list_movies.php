<?php
require_once "bootstrap.php";

use App\Entity\Movie; // Import the Movie entity


$movieRepository = $entityManager->getRepository('Movie');
$movies = $movieRepository->findAll();

foreach ($movies as $movie) {
    echo sprintf("-%s\n", $movie->getTitle());
}
