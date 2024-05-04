<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

use App\Repository\MovieRepository;

class MovieImportController extends AbstractController
{
    private $logger;
    private $movieRepository;

    public function __construct(LoggerInterface $logger, MovieRepository $movieRepository)
    {
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
    }

    #[Route('/import/movies', name: 'import_movies')]
    public function importMovies(EntityManagerInterface $entityManager): Response
    {
        // Initialize Guzzle client
        $client = new Client();

        $this->logger->info('Importing movies...');

        try {
            // Initialize batch size and counter
            $batchSize = 100;
            $counter = 0;
            $movies = []; // Initialize movies array

            // Start fetching pages
            $page = 1;
            $maxPages = 2; // Limit the pages to 2 for testing
            do {
                // Make a GET request to fetch popular movies for the current page
                $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/popular', [
                    'query' => [
                        'language' => 'en-US',
                        'page' => $page,
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmMmNmODVjYmUwNTFkYmE2MTc2Mzg2NjdlOTJiMTE0MiIsInN1YiI6IjY2MmU4N2FhMDNiZjg0MDEyYWVhZjc3MyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.nR9FGOXwU5FKtJLtH98Za5uRsDVXYtwoKz3xBV8gfng',
                        'accept' => 'application/json',
                    ],
                ]);

                // Decode the JSON response
                $data = json_decode($response->getBody(), true);

                // Iterate over movie data and add them to the movies array
                foreach ($data['results'] as $movieData) {
                    // Check if movie already exists in the database
                    if (!$this->movieRepository->findOneBy(['title' => $movieData['original_title']])) {
                        // Create a new Movie entity
                        $movie = new Movie();

                        // Set movie data
                        $movie->setTitle($movieData['original_title']);
                        $movie->setDescription($movieData['overview']);
                        $movie->setYear(isset($movieData['release_date']) ? date('Y', strtotime($movieData['release_date'])) : null);
                        $movie->setDirector('Unknown');

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
                        }
                    }
                }

                // Increment page for the next request
                $page++;
            } while ($page <= min($maxPages, $data['total_pages'])); // Ensure we don't exceed 500 pages

            // Flush any remaining entities
            $entityManager->flush();

            // Construct the response message
            $message = "Movies fetched and persisted successfully.";

            return new Response($message);
        } catch (\Exception $e) {
            // Log the error message and stack trace
            $this->logger->error('Error fetching and persisting movies: {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            // Check if the error message indicates that movies are already inserted
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // Movies are already inserted, return a response with a message
                $message = 'No new movies were imported. Movies are already inserted.';
                return new Response($message);
            }

            // Construct the error response message
            $errorMessage = 'Error fetching and persisting movies: ' . $e->getMessage();
            return new Response($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
