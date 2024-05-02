<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MovieController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/movies', name: 'movie_list')]
    public function listMovies(): Response
    {
        // Get the movie repository using the injected entityManager property
        $movieRepository = $this->entityManager->getRepository(Movie::class);

        // Fetch all movies
        $movies = $movieRepository->findAll();

        // Output movie titles
        $output = '';
        foreach ($movies as $movie) {
            $output .= sprintf("-%s\n", $movie->getTitle());
        }

        // Return a response with movie titles
        return new Response($output);
    }
}
