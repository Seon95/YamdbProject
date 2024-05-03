<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MovieController extends AbstractController
{
    #[Route('/movies', name: 'movie_list')]
    public function listMovies(EntityManagerInterface $entityManager): Response
    {
        $movies = $entityManager->getRepository(Movie::class)->findAll();

        return $this->render('movies.html.twig', [
            'movies' => $movies,
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
