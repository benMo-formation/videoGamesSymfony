<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\CategoryRepository;
use App\Repository\EditorRepository;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class VideoGameController extends AbstractController
{
    #[Route('/api/v1/video/game', name: 'video_game_list', methods: ['GET'])]
    public function getVideoGames(
        VideoGameRepository $videoGameRepository, 
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $cacheKey = "video_games_" . $page . "_" . $limit;
        
        $videoGames = $cache->get($cacheKey, function(ItemInterface $item) use ($videoGameRepository, $page, $limit) {
            $item->expiresAfter(3600); // Cache pour 1 heure
            $item->tag('videoGamesCache');
            
            $offset = ($page - 1) * $limit;
            $videoGames = $videoGameRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
            
            $totalGames = $videoGameRepository->count([]);
            $totalPages = ceil($totalGames / $limit);
            
            return [
                'data' => $videoGames,
                'meta' => [
                    'total' => $totalGames,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages
                ]
            ];
        });

        return $this->json($videoGames, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }
    
    #[Route('/api/v1/video/{id}', name: 'video_game_details', methods: ['GET'])]
    public function getVideoGame(
        VideoGame $videoGame,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cacheKey = "video_game_" . $videoGame->getId();
        
        $gameData = $cache->get($cacheKey, function(ItemInterface $item) use ($videoGame) {
            $item->expiresAfter(3600);
            $item->tag(['videoGamesCache', 'video_game_' . $videoGame->getId()]);
            
            return $videoGame;
        });
        
        return $this->json($gameData, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }

    #[Route('/api/v1/video/new', name: 'video_game_create', methods: ['POST'])]
    public function createVideoGame(
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer, 
        UrlGeneratorInterface $urlGeneratorInterface, 
        EditorRepository $editorRepository,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $videoGame = $serializer->deserialize($request->getContent(), VideoGame::class, 'json');

        $content = $request->toArray();
        
        // Récupération de l'éditeur
        $editorId = $content['editorId'] ?? null;
        if ($editorId !== null) {
            $editor = $editorRepository->find($editorId);
            if (!$editor) {
                return $this->json(['error' => 'Éditeur non trouvé'], Response::HTTP_BAD_REQUEST);
            }
            $videoGame->setEditor($editor);
        }
        
        // Récupération de la catégorie
        $categoryId = $content['categoryId'] ?? null;
        if ($categoryId !== null) {
            $category = $categoryRepository->find($categoryId);
            if (!$category) {
                return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_BAD_REQUEST);
            }
            $videoGame->setCategory($category);
        }
        
        // Association à l'utilisateur courant
        $videoGame->setUsers($this->getUser());
        
        // Validation
        $errors = $validator->validate($videoGame);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($videoGame);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['videoGamesCache']);

        $location = $urlGeneratorInterface->generate('video_game_details', ['id' => $videoGame->getId()]);
        
        return $this->json($videoGame, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'video_game_details']);
    }
    
    #[Route('/api/v1/video/{id}', name: 'video_game_update', methods: ['PUT'])]
    public function updateVideoGame(
        VideoGame $videoGame, 
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer, 
        EditorRepository $editorRepository,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $content = $request->toArray();
        
        if (isset($content['title'])) {
            $videoGame->setTitle($content['title']);
        }
        
        if (isset($content['description'])) {
            $videoGame->setDescription($content['description']);
        }
        
        if (isset($content['releaseDate'])) {
            $releaseDate = new \DateTime($content['releaseDate']);
            $videoGame->setReleaseDate($releaseDate);
        }
        
        // Mise à jour de l'éditeur si fourni
        $editorId = $content['editorId'] ?? null;
        if ($editorId !== null) {
            $editor = $editorRepository->find($editorId);
            if (!$editor) {
                return $this->json(['error' => 'Éditeur non trouvé'], Response::HTTP_BAD_REQUEST);
            }
            $videoGame->setEditor($editor);
        }
        
        // Mise à jour de la catégorie si fournie
        $categoryId = $content['categoryId'] ?? null;
        if ($categoryId !== null) {
            $category = $categoryRepository->find($categoryId);
            if (!$category) {
                return $this->json(['error' => 'Catégorie non trouvée'], Response::HTTP_BAD_REQUEST);
            }
            $videoGame->setCategory($category);
        }

        // Validation
        $errors = $validator->validate($videoGame);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['videoGamesCache', 'video_game_' . $videoGame->getId()]);

        return $this->json($videoGame, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }

    #[Route('/api/v1/video/{id}', name: 'video_game_delete', methods: ['DELETE'])]
    public function deleteVideoGame(
        VideoGame $videoGame, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($videoGame);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['videoGamesCache', 'video_game_' . $videoGame->getId()]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
