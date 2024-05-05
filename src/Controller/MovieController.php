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
        $this->logger->info('Listing movies');

        $query = $request->query->get('query');

        $currentPage = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        $cacheKey = 'movies_' . md5($query . '_' . $currentPage);

        $movies = $this->cache->get($cacheKey, function () use ($query, $currentPage, $limit) {
            $movies = $this->fetchMovies($query, $currentPage, $limit);
            return $movies;
        }, 3600);

        return $this->render('movies.html.twig', [
            'movies' => $movies,
            'currentPage' => $currentPage,
            'limit' => $limit,
            'query' => $query,
        ]);
    }

    #[Route('/movies/{id}', name: 'movie_detail')]
    public function detail(Movie $movie, Request $request): Response
    {
        $this->logger->info('Viewing movie detail: {movie}', ['movie' => $movie->getTitle()]);

        $currentPage = $request->query->getInt('page', 1);

        return $this->render('movie_detail.html.twig', [
            'movie' => $movie,
            'currentPage' => $currentPage,
        ]);
    }

    private function fetchMovies(string $query = null, int $currentPage, int $limit): array
    {
        if ($query) {
            $this->logger->info('Performing movie search for query: {query}', ['query' => $query]);
            $paginator = $this->movieRepository->searchByTitle($query, $currentPage, $limit);
        } else {
            $this->logger->info('Retrieving paginated movies');
            $paginator = $this->movieRepository->findPaginated($currentPage, $limit);
        }

        $movies = [];
        foreach ($paginator as $movie) {
            $movies[] = $movie;
        }

        return $movies;
    }
}
