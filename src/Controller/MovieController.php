<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

class MovieController extends AbstractController
{
    private $cache;
    private $entityManager;
    private $movieRepository;
    private $logger;

    public function __construct(CacheInterface $cache, EntityManagerInterface $entityManager, MovieRepository $movieRepository, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->movieRepository = $movieRepository;
        $this->logger = $logger;
    }

    #[Route('/movies', name: 'movie_list')]
    public function listMovies(Request $request): Response
    {
        // Log a message indicating the start of the movie listing process
        $this->logger->info('Listing movies');

        // Get the search query from the request
        $query = $request->query->get('query');

        // Get the current page from the request
        $currentPage = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        // Define the cache key based on the query and page number
        $cacheKey = 'movies_' . md5($query . '_' . $currentPage);

        // Use Symfony's cache to retrieve or save data
        // Use Symfony's cache to retrieve or save data with a specific lifetime (e.g., 3600 seconds = 1 hour)
        $movies = $this->cache->get($cacheKey, function () use ($query, $currentPage, $limit) {
            // Fetch movies from the database
            $movies = $this->fetchMovies($query, $currentPage, $limit);

            // Return movies to cache with a specific lifetime (e.g., 3600 seconds = 1 hour)
            return $movies;
        }, 3600); // Set cache with a lifetime of 1 hour (3600 seconds)


        return $this->render('movies.html.twig', [
            'movies' => $movies,
            'currentPage' => $currentPage,
            'limit' => $limit,
            'query' => $query, // Pass the query to the template for display
        ]);
    }

    #[Route('/movies/{id}', name: 'movie_detail')]
    public function detail(Movie $movie, Request $request): Response
    {
        // Log a message indicating that a movie detail view is being accessed
        $this->logger->info('Viewing movie detail: {movie}', ['movie' => $movie->getTitle()]);

        // Get the current page number from the query parameters
        $currentPage = $request->query->getInt('page', 1);

        return $this->render('movie_detail.html.twig', [
            'movie' => $movie,
            'currentPage' => $currentPage,
        ]);
    }

    private function fetchMovies(string $query = null, int $currentPage, int $limit): array
    {
        // If there is a search query, perform the search
        if ($query) {
            // Log a message indicating that a search query is being processed
            $this->logger->info('Performing movie search for query: {query}', ['query' => $query]);
            $paginator = $this->movieRepository->searchByTitle($query, $currentPage, $limit);
        } else {
            // Log a message indicating that paginated movies are being retrieved
            $this->logger->info('Retrieving paginated movies');
            $paginator = $this->movieRepository->findPaginated($currentPage, $limit);
        }

        // Extract items from the paginator and convert to array
        $movies = [];
        foreach ($paginator as $movie) {
            $movies[] = $movie;
        }

        return $movies;
    }
}
