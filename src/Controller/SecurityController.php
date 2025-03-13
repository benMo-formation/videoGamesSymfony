<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SecurityController extends AbstractController
{
    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var Users $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->json([
            'username' => $user->getUserName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], Response::HTTP_OK, [], ['groups' => 'user_details']);
    }
    
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
            return $this->json([
                'message' => 'Données manquantes pour l\'inscription',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $user = new Users();
        $user->setUserName($data['username']);
        $user->setEmail($data['email']);
        
        // Hachage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        // Par défaut, les nouveaux utilisateurs ont le rôle USER
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'username' => $user->getUserName(),
        ], Response::HTTP_CREATED);
    }
}