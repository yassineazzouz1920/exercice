<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController','identifiant'=>5
        ]);
    }

    #[Route('/hello/{id}',name:'hello')]
    public function hello($id){
        // return new Response("Hello 3A25".$id);
        return $this->render('home/index.html.twig',['identifiant'=>$id]); 
    }
}
