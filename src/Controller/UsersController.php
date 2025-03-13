<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UsersController extends AbstractController
{
    #[Route('/api/v1/user/list', name: 'user_list', methods: ['GET'])]
    public function getUsers(
        UsersRepository $usersRepository,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $cacheKey = "users_" . $page . "_" . $limit;
        
        $users = $cache->get($cacheKey, function(ItemInterface $item) use ($usersRepository, $page, $limit) {
            $item->expiresAfter(3600); // Cache pour 1 heure
            $item->tag('usersCache');
            
            $offset = ($page - 1) * $limit;
            $users = $usersRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
            
            $totalUsers = $usersRepository->count([]);
            $totalPages = ceil($totalUsers / $limit);
            
            return [
                'data' => $users,
                'meta' => [
                    'total' => $totalUsers,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages
                ]
            ];
        });

        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'user_details']);
    }
    
    #[Route('/api/v1/user/{id}', name: 'user_details', methods: ['GET'])]
    public function getUserDetails(
        Users $user,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Seul un admin ou l'utilisateur lui-même peut voir ses détails
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
        
        $cacheKey = "user_" . $user->getId();
        
        $userData = $cache->get($cacheKey, function(ItemInterface $item) use ($user) {
            $item->expiresAfter(3600);
            $item->tag(['usersCache', 'user_' . $user->getId()]);
            
            return $user;
        });
        
        return $this->json($userData, Response::HTTP_OK, [], ['groups' => 'user_details']);
    }

    #[Route('/api/v1/user/new', name: 'user_create', methods: ['POST'])]
    public function createUser(
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer, 
        UrlGeneratorInterface $urlGeneratorInterface,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $serializer->deserialize($request->getContent(), Users::class, 'json');
        
        // Hachage du mot de passe
        $plaintextPassword = $user->getPassword();
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);
        
        // Par défaut, donner le rôle ROLE_USER
        $user->setRoles(['ROLE_USER']);
        
        // Validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['usersCache']);

        $location = $urlGeneratorInterface->generate('user_details', ['id' => $user->getId()]);
        
        return $this->json($user, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'user_details']);
    }
    
    #[Route('/api/v1/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function updateUser(
        Users $user, 
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        // Seul un admin ou l'utilisateur lui-même peut se mettre à jour
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }
        
        $content = $request->toArray();
        
        if (isset($content['userName'])) {
            $user->setUserName($content['userName']);
        }
        
        if (isset($content['email'])) {
            $user->setEmail($content['email']);
        }
        
        // Seul un admin peut changer les rôles
        if (isset($content['roles']) && $this->isGranted('ROLE_ADMIN')) {
            $user->setRoles($content['roles']);
        }
        
        // Mise à jour du mot de passe si fourni
        if (isset($content['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $content['password']);
            $user->setPassword($hashedPassword);
        }
        
        // Validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['usersCache', 'user_' . $user->getId()]);

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_details']);
    }

    #[Route('/api/v1/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(
        Users $user, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($user);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['usersCache', 'user_' . $user->getId()]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}