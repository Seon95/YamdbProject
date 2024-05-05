<?php

require_once "bootstrap.php";
require_once __DIR__ . "/vendor/autoload.php";

use App\Entity\Movie;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// Initialize Guzzle client
$client = new Client();

try {
    // Initialize batch size and counter
    $batchSize = 100;
    $counter = 0;
    $movies = []; // Initialize movies array

    // Start fetching pages
    $page = 1;
    $maxPages = 100; // Limit the pages to 500

    $promises = [];

    do {
        // Make a GET request to fetch popular movies for the current page
        $promises[] = $client->getAsync('https://api.themoviedb.org/3/movie/popular', [
            'query' => [
                'language' => 'en-US',
                'page' => $page,
            ],
            'headers' => [
                'Authorization' => "Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmMmNmODVjYmUwNTFkYmE2MTc2Mzg2NjdlOTJiMTE0MiIsInN1YiI6IjY2MmU4N2FhMDNiZjg0MDEyYWVhZjc3MyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.nR9FGOXwU5FKtJLtH98Za5uRsDVXYtwoKz3xBV8gfng", // Access the API key from environment variables
                'accept' => 'application/json',
            ],
        ]);

        $page++;
    } while ($page <= min($maxPages, $maxPages)); // Ensure we don't exceed 500 pages

    // Wait for all promises to complete
    $responses = Promise\Utils::settle($promises)->wait();

    foreach ($responses as $response) {
        // Check if the response is fulfilled
        if ($response['state'] === Promise\PromiseInterface::FULFILLED) {
            // Decode JSON response
            $data = json_decode($response['value']->getBody(), true);

            foreach ($data['results'] as $movieData) {
                // Create a new Movie entity
                $movie = new Movie();

                // Set movie data
                $movie->setTitle($movieData['original_title']);
                $movie->setDescription($movieData['overview']);
                $movie->setYear(isset($movieData['release_date']) ? date('Y', strtotime($movieData['release_date'])) : null);

                // Make a new request to get the credits of the movie
                $creditsResponse = $client->get('https://api.themoviedb.org/3/movie/' . $movieData['id'] . '/credits', [
                    'query' => [
                        'language' => 'en-US',
                    ],
                    'headers' => [
                        'Authorization' => "Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmMmNmODVjYmUwNTFkYmE2MTc2Mzg2NjdlOTJiMTE0MiIsInN1YiI6IjY2MmU4N2FhMDNiZjg0MDEyYWVhZjc3MyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.nR9FGOXwU5FKtJLtH98Za5uRsDVXYtwoKz3xBV8gfng", // Access the API key from environment variables
                        'accept' => 'application/json',
                    ],
                ]);

                $creditsData = json_decode($creditsResponse->getBody(), true);

                // Find the director from the crew
                $directorName = 'Unknown';
                foreach ($creditsData['crew'] as $crewMember) {
                    if ($crewMember['job'] === 'Director') {
                        $directorName = $crewMember['name'];
                        break;
                    }
                }

                // Set the director of the movie
                $movie->setDirector($directorName);

                // Persist the movie entity
                $entityManager->persist($movie);

                // Add movie data to the movies array
                $movies[] = $movie;

                // Increment the counter
                $counter++;

                // Flush the EntityManager every $batchSize iterations
                if ($counter % $batchSize === 0) {
                    $entityManager->flush();
                    $entityManager->clear(); // Detach all objects from Doctrine's management
                    echo "Processed $counter movies.\n";
                }
            }
        } else {
            // Handle rejected promises
            echo "Error: " . $response['reason'] . "\n";
        }
    }

    // Flush any remaining entities
    $entityManager->flush();

    echo "Movies fetched and persisted successfully.\n";

    return $movies; // Return the fetched movies array
} catch (\Exception $e) {
    // Handle exceptions
    echo 'Error fetching and persisting movies: ' . $e->getMessage();
}
