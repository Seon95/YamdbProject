<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MovieController extends AbstractController
{
    #[Route('/movies', name: 'movie_list')]
    public function listMovies(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the search query from the request
        $query = $request->query->get('query');

        // If there is a search query, perform the search
        if ($query) {
            $movies = $entityManager->getRepository(Movie::class)->searchByTitle($query);
        } else {
            // If no search query, retrieve all movies
            $movies = $entityManager->getRepository(Movie::class)->findAll();
        }

        return $this->render('movies.html.twig', [
            'movies' => $movies,
            'query' => $query, // Pass the query to the template for display
        ]);
    }

    #[Route('/movies/{id}', name: 'movie_detail')]
    public function detail(Movie $movie): Response
    {
        return $this->render('movie_detail.html.twig', [
            'movie' => $movie,
        ]);
    }
}
