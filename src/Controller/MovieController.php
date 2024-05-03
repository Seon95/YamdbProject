<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MovieRepository;


class MovieController extends AbstractController
{
    #[Route('/movies', name: 'movie_list')]
    public function listMovies(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentPage = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        // Use the repository directly
        $paginator = $entityManager->getRepository(Movie::class)->findPaginated($currentPage, $limit);

        return $this->render('movies.html.twig', [
            'movies' => $paginator,
            'currentPage' => $currentPage,
            'limit' => $limit,
        ]);
    }


    #[Route('/movies/{id}', name: 'movie_detail')]
    public function detail(Movie $movie, Request $request): Response
    {
        // Get the current page number from the query parameters
        $currentPage = $request->query->getInt('page', 1);

        return $this->render('movie_detail.html.twig', [
            'movie' => $movie,
            'currentPage' => $currentPage,
        ]);
    }
}
