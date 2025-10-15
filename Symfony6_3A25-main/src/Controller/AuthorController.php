<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorType;
use App\Repository\AuthorRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthorController extends AbstractController
{
    #[Route('/author', name: 'app_author')]
    public function index(): Response
    {
        return $this->render('author/index.html.twig', [
            'controller_name' => 'AuthorController',
        ]);
    }

    #[Route('/show/{name}', name: 'showAuthor')]
    public function showAuthor($name)
    {
        return $this->render(
            'author/show.html.twig'
            ,
            ['nom' => $name, 'prenom' => 'ben foulen']
        );
    }

    #[Route('/ShowAll', name: 'ShowAll')]
    public function ShowAll(AuthorRepository $repo)
    {
        $authors = $repo->findAll();
        return $this->render(
            'author/showAll.html.twig'
            ,
            ['list' => $authors]
        );
    }

    #[Route('/addStat', name: 'addStat')]
    public function addStat(ManagerRegistry $doctrine)
    {
        $author = new Author();
        $author->setEmail('Test@gmail.com');
        $author->setUsername('foulen');
        $author->setNbBooks(0);
        $em = $doctrine->getManager();
        $em->persist($author);
        $em->flush();

        // return new Response("Author added succesfully");
        return $this->redirectToRoute('ShowAll');
    }

    #[Route('/addForm', name: 'addForm')]
    public function addForm(ManagerRegistry $doctrine, Request $request)
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->add('add', SubmitType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $em = $doctrine->getManager();
            $em->persist($author);
            $em->flush();

            // return new Response("Author added succesfully");
            return $this->redirectToRoute('ShowAll');
        }
        return $this->render(
            'author/add.html.twig',
            ['formulaire' => $form->createView()]
        );
    }

    #[Route('/deleteAuthor/{id}', name: 'deleteAuthor')]
    public function deleteAuthor($id, AuthorRepository $repo, ManagerRegistry $manager)
    {
        $author = $repo->find($id);
        $em = $manager->getManager();
        $em->remove($author);
        $em->flush();
        return $this->redirectToRoute('ShowAll');
    }

    #[Route('/showAuthorDetails/{id}', name: 'showAuthorDetails')]
    public function showAuthorDetails($id, AuthorRepository $repo)
    {
        $author = $repo->find($id);
        return $this->render('author/showDetails.html.twig', ['author' => $author]);
    }

    #[Route('/editAuthor/{id}', name: 'editAuthor')]
    public function editAuthor($id, AuthorRepository $repo, Request $request, ManagerRegistry $doctrine)
    {
        // Récupérer l'auteur par son ID
        $author = $repo->find($id);
        
        // Vérifier si l'auteur existe
        if (!$author) {
            $this->addFlash('error', 'Auteur non trouvé !');
            return $this->redirectToRoute('ShowAll');
        }

        // Créer le formulaire avec les données de l'auteur existant
        $form = $this->createForm(AuthorType::class, $author);
        $form->add('update', SubmitType::class, [
            'label' => 'Mettre à jour',
            'attr' => ['class' => 'btn btn-primary']
        ]);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Logique métier avant la mise à jour
                $this->validateAuthorData($author);
                
                // Sauvegarder les modifications
                $em = $doctrine->getManager();
                $em->persist($author);
                $em->flush();

                // Message de succès
                $this->addFlash('success', 'Auteur mis à jour avec succès !');
                
                // Rediriger vers la liste des auteurs
                return $this->redirectToRoute('ShowAll');
                
            } catch (\Exception $e) {
                // Gestion des erreurs
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        // Afficher le formulaire d'édition
        return $this->render('author/edit.html.twig', [
            'formulaire' => $form->createView(),
            'author' => $author
        ]);
    }

    #[Route('/updateAuthor/{id}', name: 'updateAuthor', methods: ['POST'])]
    public function updateAuthor($id, AuthorRepository $repo, Request $request, ManagerRegistry $doctrine)
    {
        // Récupérer l'auteur par son ID
        $author = $repo->find($id);
        
        if (!$author) {
            $this->addFlash('error', 'Auteur non trouvé !');
            return $this->redirectToRoute('ShowAll');
        }

        // Créer le formulaire
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Logique métier de validation
                $this->validateAuthorData($author);
                
                // Logique métier spécifique à la mise à jour
                $this->processAuthorUpdate($author);

                // Sauvegarder
                $em = $doctrine->getManager();
                $em->flush();

                $this->addFlash('success', 'Auteur mis à jour avec succès !');
                return $this->redirectToRoute('showAuthorDetails', ['id' => $author->getId()]);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('author/edit.html.twig', [
            'formulaire' => $form->createView(),
            'author' => $author
        ]);
    }

    /**
     * Logique métier : Validation des données de l'auteur
     */
    private function validateAuthorData(Author $author): void
    {
        // Validation de l'email
        if (!filter_var($author->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Format d\'email invalide');
        }

        // Validation du nom d'utilisateur
        if (empty(trim($author->getUsername()))) {
            throw new \InvalidArgumentException('Le nom d\'utilisateur ne peut pas être vide');
        }

        // Validation du nombre de livres
        if ($author->getNbBooks() < 0) {
            throw new \InvalidArgumentException('Le nombre de livres ne peut pas être négatif');
        }

        // Validation de la longueur du nom d'utilisateur
        if (strlen($author->getUsername()) > 255) {
            throw new \InvalidArgumentException('Le nom d\'utilisateur est trop long (max 255 caractères)');
        }
    }

    /**
     * Logique métier : Traitement spécifique lors de la mise à jour
     */
    private function processAuthorUpdate(Author $author): void
    {
        // Mettre à jour la date de dernière modification (si vous avez ce champ)
        // $author->setUpdatedAt(new \DateTime());
        
        // Logique métier spécifique : par exemple, vérifier si le nombre de livres correspond aux livres réels
        $actualBookCount = $author->getBooks()->count();
        if ($author->getNbBooks() !== $actualBookCount) {
            // Optionnel : synchroniser automatiquement ou laisser l'utilisateur décider
            // $author->setNbBooks($actualBookCount);
        }

        // Autres logiques métier selon vos besoins
        // - Envoyer une notification
        // - Logger l'action
        // - Mettre à jour des statistiques
    }

}
