<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FoodItemService;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use App\Constants\FoodItemTypes;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FoodItemController extends AbstractController
{
    private $foodItemService;
    private $serializer;

    public function __construct(FoodItemService $foodItemService, SerializerInterface $serializer)
    {
        $this->foodItemService = $foodItemService;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/api/process-fooditems",name="process_food_items", methods={"POST"})
     *
     * Handles the processing of a list of food items from the request.
     * Validates and processes the JSON payload and returns the result.
     */
    public function processRequest(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Get the JSON payload from the request body
        $jsonData = $request->getContent();

        try {
            // Decode the JSON data
            $data = json_decode($jsonData, true);
            $processedData = $this->foodItemService->processRequestJson($data, $validator);
            $jsonContent = $this->serializer->serialize($processedData, 'json', ['groups' => 'food_item:read']);
            if ($processedData['successful']) {
                return new JsonResponse($jsonContent, JsonResponse::HTTP_CREATED, [], true);
            } else {
                return new JsonResponse($jsonContent, JsonResponse::HTTP_UNPROCESSABLE_ENTITY, [], true);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    /**
     * @Route("/api/fooditems", name="food_items", methods={"GET"})
     * 
     * Lists all food items, optionally converting quantities to a specified unit.
     */
    public function list(Request $request): JsonResponse
    {
        $unit = $request->query->get('unit', 'grams');
        $foodItems = $this->foodItemService->listFoodItemsByType($unit);
        $jsonContent = $this->serializer->serialize($foodItems, 'json', ['groups' => 'food_item:read']);
        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/fooditems/add", name="add_food_item", methods={"POST"})
     */
    public function addItem(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            $foodItem = $this->foodItemService->addFoodItem($data);
            $jsonContent = $this->serializer->serialize($foodItem, 'json', ['groups' => 'food_item:read']);
            return new JsonResponse($jsonContent, JsonResponse::HTTP_CREATED, [], true);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getCode() ?: 400); // Handle exceptions
        }
    }

    /**
     * @Route("/api/fooditems/remove/{id}", name="delete_food_item", methods={"DELETE"})
     */
    public function delete(int $id): JsonResponse
    {
        $foodItem = $this->foodItemService->deleteFoodItemById($id);
        if (!$foodItem) {
            return new JsonResponse(['message' => 'FoodItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        return new JsonResponse(['message' => 'FoodItem deleted successfully'], JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/api/fooditems/search/{type?}", name="search_food_items", methods={"GET"})
     * 
     * Searches for food items based on criteria and an optional type.
     */
    public function search(Request $request, ?string $type = null): JsonResponse
    {
        $criteria = $request->query->all();
        try {
            $foodItems = $this->foodItemService->searchFoodItemsByType($type, $criteria);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
        $jsonContent = $this->serializer->serialize($foodItems, 'json', ['groups' => 'food_item:read']);
        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/fooditems/add/{type}", name="add_food_items_by_type", methods={"POST"})
     * 
     * Adds food items of a specific type based on the provided JSON payload.
     */
    public function addItemsByTpe(Request $request, string $type, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!in_array($type, FoodItemTypes::getAllowedTypes())) {
            return new JsonResponse(['error' => 'Invalid type. Allowed types are "fruit" and "vegetable".'], JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            $processedData = $this->foodItemService->processRequestJson($data, $validator, $type);
            $jsonContent = $this->serializer->serialize($processedData, 'json', ['groups' => 'food_item:read']);
            if ($processedData['successful']) {
                return new JsonResponse($jsonContent, JsonResponse::HTTP_CREATED, [], true);
            } else {
                return new JsonResponse($jsonContent, JsonResponse::HTTP_UNPROCESSABLE_ENTITY, [], true);
            }
        } catch (\Exception $e) {
            return new JsonResponse('Failed to add food item: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * @Route("/api/test-process-fooditems", name="test_process_food_items", methods={"GET"})
     * 
     * Handles processing of test data for food items.
     * Reads test data from a configuration file, processes it, and updates the configuration status.
     */
    public function testProcessRequest(ValidatorInterface $validator, ParameterBagInterface $params): JsonResponse
    {
        $testDataFile = $params->get('test_data_file');

        $config = json_decode(file_get_contents($testDataFile), true);

       // $configFile = __DIR__ . '/../../config/test_data.json';
        // Read the configuration file
       // $config = json_decode(file_get_contents($configFile), true);
        // Check if test data has already been processed
        if ($config['processed']) {
            return new JsonResponse(['message' => 'Test data has already been processed.'], JsonResponse::HTTP_OK);
        }

        $jsonData = json_encode($config['data']);
        $request = Request::create('/api/process-fooditems', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $jsonData);
        $response = $this->processRequest($request, $validator);
        if ($response->getStatusCode() === JsonResponse::HTTP_CREATED) {
            // Update the status to indicate the test data has been processed
            $config['processed'] = true;
            file_put_contents($testDataFile, json_encode($config));
        }
        return $response;
    }
}
