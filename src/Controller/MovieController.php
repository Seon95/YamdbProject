<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class MovieController extends AbstractController
{
    #[Route('/movies', name: 'movie_list')]
    public function listMovies(Request $request, EntityManagerInterface $entityManager, MovieRepository $movieRepository, LoggerInterface $logger): Response
    {
        // Log a message indicating the start of the movie listing process
        $logger->info('Listing movies');

        // Get the search query from the request
        $query = $request->query->get('query');

        // Get the current page from the request
        $currentPage = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        // Initialize an empty movies array
        $movies = [];

        // If there is a search query, perform the search
        if ($query) {
            // Log a message indicating that a search query is being processed
            $logger->info('Performing movie search for query: {query}', ['query' => $query]);

            $movies = $movieRepository->searchByTitle($query);
        } else {
            // Log a message indicating that paginated movies are being retrieved
            $logger->info('Retrieving paginated movies');

            // If no search query, retrieve paginated movies
            $movies = $movieRepository->findPaginated($currentPage, $limit);
        }

        return $this->render('movies.html.twig', [
            'movies' => $movies,
            'currentPage' => $currentPage,
            'limit' => $limit,
            'query' => $query, // Pass the query to the template for display
        ]);
    }

    #[Route('/movies/{id}', name: 'movie_detail')]
    public function detail(Movie $movie, Request $request, LoggerInterface $logger): Response
    {
        // Log a message indicating that a movie detail view is being accessed
        $logger->info('Viewing movie detail: {movie}', ['movie' => $movie->getTitle()]);

        // Get the current page number from the query parameters
        $currentPage = $request->query->getInt('page', 1);

        return $this->render('movie_detail.html.twig', [
            'movie' => $movie,
            'currentPage' => $currentPage,
        ]);
    }
}
