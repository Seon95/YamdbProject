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
$streamHandler = new StreamHandler('var/log/dev.log', Level::Debug);
$logger->pushHandler($streamHandler);
$logger->info("Starting the process of sending movies.");

$client = new Client();

try {
    $batchSize = 100;
    $counter = 0;
    $movies = [];
    $processedMovieIds = [];

    $page = 1;
    $promises = [];

    do {
        $promises[] = $client->getAsync('https://api.themoviedb.org/3/movie/popular', [
            'query' => [
                'language' => 'en-US',
                'page' => $page,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $_ENV['API_KEY'],
                'accept' => 'application/json',
            ],
        ]);

        $page++;

        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $response) {
            if ($response['state'] === Promise\PromiseInterface::FULFILLED) {
                $data = json_decode($response['value']->getBody(), true);

                foreach ($data['results'] as $movieData) {
                    if (in_array($movieData['id'], $processedMovieIds)) {
                        continue;
                    }

                    $processedMovieIds[] = $movieData['id'];

                    $movie = new Movie();

                    $movie->setTitle($movieData['original_title']);
                    $movie->setDescription($movieData['overview']);
                    $movie->setYear(isset($movieData['release_date']) ? date('Y', strtotime($movieData['release_date'])) : null);
                    $movie->setDirector("Unknown");
                    $entityManager->persist($movie);

                    $movies[] = $movie;

                    $counter++;

                    if ($counter % $batchSize === 0) {
                        $entityManager->flush();
                        $entityManager->clear();
                        echo "Processed $counter movies.\n";
                    }
                }
            } else {
                $errorResponse = $response['reason'];
                if ($errorResponse instanceof ClientException) {
                    $statusCode = $errorResponse->getResponse()->getStatusCode();
                    if ($statusCode === 400) {
                        break 2;
                    } else {
                        echo ("Error: " . $errorResponse->getMessage());
                        $logger->error("Error: " . $errorResponse->getMessage());
                    }
                } else {
                    $logger->error("Error: " . $errorResponse->getMessage());
                    echo ("Error: " . $errorResponse->getMessage());
                }
            }
        }
    } while (true);

    $entityManager->flush();

    $streamHandler->close();
    $logger->info("Movies fetched and persisted successfully.");
    echo "Processed $counter movies.\n";
    echo ("Movies fetched and persisted successfully.\n");


    return $movies;
} catch (\Exception $e) {
    $logger->error('Error fetching and persisting movies: ' . $e->getMessage());
    echo ('Error fetching and persisting movies: ' . $e->getMessage());
}
