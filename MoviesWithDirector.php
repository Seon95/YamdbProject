<?php

require_once "bootstrap.php";
require_once __DIR__ . "/vendor/autoload.php";

use App\Entity\Movie;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Symfony\Component\Dotenv\Dotenv;



$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');
$logger = new Logger("my_logger");
$streamHandler = new StreamHandler('var/log/dev.log', Level::Debug); // Use DEBUG level for all log messages
$logger->pushHandler($streamHandler);
// Initialize Guzzle client
$client = new Client();

try {
    // Initialize batch size and counter
    $batchSize = 100;
    $counter = 0;
    $movies = []; // Initialize movies array
    $processedMovieIds = []; // Initialize array to store processed movie IDs

    // Start fetching pages
    $page = 1;

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

        // Wait for all promises to complete
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $response) {
            // Check if the response is fulfilled
            if ($response['state'] === Promise\PromiseInterface::FULFILLED) {
                // Decode JSON response
                $data = json_decode($response['value']->getBody(), true);

                foreach ($data['results'] as $movieData) {
                    // Check if movie ID has already been processed
                    if (in_array($movieData['id'], $processedMovieIds)) {
                        continue; // Skip processing if movie ID already exists
                    }

                    // Add movie ID to processed list
                    $processedMovieIds[] = $movieData['id'];

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
                            'Authorization' => 'Bearer ' . $_ENV['API_KEY'],
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
                        // Flush the EntityManager
                        $entityManager->flush();
                        $entityManager->clear(); // Detach all objects from Doctrine's management
                        echo "Processed $counter movies.\n";
                    }
                }
            } else {
                // Handle rejected promises
                $errorResponse = $response['reason'];
                if ($errorResponse instanceof ClientException) {
                    $statusCode = $errorResponse->getResponse()->getStatusCode();
                    if ($statusCode === 400) {
                        // Stop fetching if a 400 error occurs (Bad Request)
                        break 2; // Break out of both inner and outer loops
                    } else {
                        $logger->error("Error: " . $errorResponse->getMessage());
                    }
                } else {
                    $logger->error("Error: " . $errorResponse->getMessage());
                }
            }
        }
    } while (true);

    // Flush any remaining entities
    $entityManager->flush();

    $streamHandler->close(); // Close the stream handler

    $logger->info("Movies fetched and persisted successfully.");
    echo "Processed $counter movies.\n";

    return $movies; // Return the fetched movies array
} catch (\Exception $e) {
    // Handle exceptions
    $logger->error('Error fetching and persisting movies: ' . $e->getMessage());
}
