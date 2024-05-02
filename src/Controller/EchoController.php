<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EchoController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/echo', name: 'echo')]
    public function echoEntityManager(): Response
    {
        // Check if the $entityManager is initialized
        if ($this->entityManager) {
            // Echo the EntityManager object
            return new Response('<pre>' . print_r($this->entityManager, true) . '</pre>');
        } else {
            // If $entityManager is not initialized, return an error message
            return new Response('EntityManager not found.');
        }
    }
}
