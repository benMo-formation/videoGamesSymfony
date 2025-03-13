<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CategoryController extends AbstractController
{
    #[Route('/api/v1/category', name: 'category_list', methods: ['GET'])]
    public function getCategories(
        CategoryRepository $categoryRepository, 
        Request $request, 
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $cacheKey = "categories_" . $page . "_" . $limit;
        
        $categories = $cache->get($cacheKey, function(ItemInterface $item) use ($categoryRepository, $page, $limit) {
            $item->expiresAfter(3600); // Cache for 1 hour
            $item->tag('categoriesCache');
            
            $offset = ($page - 1) * $limit;
            $categories = $categoryRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
            
            $totalCategories = $categoryRepository->count([]);
            $totalPages = ceil($totalCategories / $limit);
            
            return [
                'data' => $categories,
                'meta' => [
                    'total' => $totalCategories,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages
                ]
            ];
        });

        return $this->json($categories, Response::HTTP_OK, [], ['groups' => 'category_details']);
    }
    
    #[Route('/api/v1/category/{id}', name: 'category_details', methods: ['GET'])]
    public function getCategoryById(
        Category $category, 
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $cacheKey = "category_" . $category->getId();
        
        $categoryData = $cache->get($cacheKey, function(ItemInterface $item) use ($category) {
            $item->expiresAfter(3600);
            $item->tag(['categoriesCache', 'category_' . $category->getId()]);
            
            return $category;
        });
        
        return $this->json($categoryData, Response::HTTP_OK, [], ['groups' => 'category_details']);
    }

    #[Route('/api/v1/category/new', name: 'category_create', methods: ['POST'])]
    public function createCategory(
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer, 
        UrlGeneratorInterface $urlGeneratorInterface,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');
        
        // Validation
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($category);
        $em->flush();
        
        // Invalider le cache pour forcer un rafraîchissement
        $cache->invalidateTags(['categoriesCache']);

        $location = $urlGeneratorInterface->generate('category_details', ['id' => $category->getId()]);
        
        return $this->json($category, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'category_details']);
    }
    
    #[Route('/api/v1/category/{id}', name: 'category_update', methods: ['PUT'])]
    public function updateCategory(
        Category $category, 
        EntityManagerInterface $em, 
        Request $request, 
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = $request->getContent();
        $updatedCategory = $serializer->deserialize($data, Category::class, 'json');
        
        $category->setName($updatedCategory->getName());
        
        // Validation
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['categoriesCache', 'category_' . $category->getId()]);

        return $this->json($category, Response::HTTP_OK, [], ['groups' => 'category_details']);
    }

    #[Route('/api/v1/category/{id}', name: 'category_delete', methods: ['DELETE'])]
    public function deleteCategory(
        Category $category, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em->remove($category);
        $em->flush();
        
        // Invalider le cache
        $cache->invalidateTags(['categoriesCache', 'category_' . $category->getId()]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
