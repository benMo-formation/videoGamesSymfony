<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
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

class EditorController extends AbstractController
{
    #[Route('/api/v1/editor', name: 'editor_list', methods: ['GET'])]
    public function getEditors(
        EditorRepository $editorRepository,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $cacheKey = "editors_" . $page . "_" . $limit;
        
        $editors = $cache->get($cacheKey, function(ItemInterface $item) use ($editorRepository, $page, $limit) {
            $item->expiresAfter(3600); // Cache pour 1 heure
            $item->tag('editorsCache');
            
            $offset = ($page - 1) * $limit;
            $editors = $editorRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
            
            $totalEditors = $editorRepository->count([]);
            $totalPages = ceil($totalEditors / $limit);
            
            return [
                'data' => $editors,
                'meta' => [
                    'total' => $totalEditors,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages
                ]
            ];
        });

        return $this->json($editors, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }
    
    #[Route('/api/v1/editor/{id}', name: 'editor_details', methods: ['GET'])]
    public function getEditorById(
        Editor $editor,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cacheKey = "editor_" . $editor->getId();
        
        $editorData = $cache->get($cacheKey, function(ItemInterface $item) use ($editor) {
            $item->expiresAfter(3600);
            $item->tag(['editorsCache', 'editor_' . $editor->getId()]);
            
            return $editor;
        });
        
        return $this->json($editorData, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }

    #[Route('/api/v1/editor/new', name: 'editor_create', methods: ['POST'])]
    public function createEditor(
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer, 
        UrlGeneratorInterface $urlGeneratorInterface,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $editor = $serializer->deserialize($request->getContent(), Editor::class, 'json');
        
        // Validation
        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($editor);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['editorsCache']);

        $location = $urlGeneratorInterface->generate('editor_details', ['id' => $editor->getId()]);
        
        return $this->json($editor, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'video_game_details']);
    }
    
    #[Route('/api/v1/editor/{id}', name: 'editor_update', methods: ['PUT'])]
    public function updateEditor(
        Editor $editor, 
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $content = $request->toArray();
        
        if (isset($content['name'])) {
            $editor->setName($content['name']);
        }
        
        if (isset($content['country'])) {
            $editor->setCountry($content['country']);
        }
        
        // Validation
        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['editorsCache', 'editor_' . $editor->getId()]);

        return $this->json($editor, Response::HTTP_OK, [], ['groups' => 'video_game_details']);
    }

    #[Route('/api/v1/editor/{id}', name: 'editor_delete', methods: ['DELETE'])]
    public function deleteEditor(
        Editor $editor, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($editor);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['editorsCache', 'editor_' . $editor->getId()]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
