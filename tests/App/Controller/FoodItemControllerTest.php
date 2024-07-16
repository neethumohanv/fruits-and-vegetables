<?php
namespace App\Tests\App\Controller;

use App\Entity\FoodItem;
use App\Service\FoodItemService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FoodItemControllerTest extends WebTestCase
{
    
    public function testAddFoodItem()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/fooditems/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                "name" => "Banana",
                "type" => "fruit",
                "quantity" => 1000,
                "unit" => "kg"
            ])
        );

        $response = $client->getResponse();
        $responseContent = $response->getContent();

        // Debugging statements
      //  error_log('Response status code: ' . $response->getStatusCode());
       // error_log('Response content: ' . $responseContent);

        // Assert the response status code
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Assert the response is in JSON format
        $this->assertJson($responseContent);
    
    }

    public function testProcessFoodItems(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/process-fooditems',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                ["name" => "Banana", "type" => "fruit", "quantity" => 1000, "unit" => "kg"],
                ["name" => "Apple", "type" => "fruit", "quantity" => 500, "unit" => "kg"],
                ["name" => "Carrot", "type" => "vegetable", "quantity" => 5000, "unit" => "g"],
            ])
        );

        $response = $client->getResponse();
        $responseContent = $response->getContent();

        // Debugging statements
    //    error_log('Response status code: ' . $response->getStatusCode());
     //   error_log('Response content: ' . $responseContent);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    

    public function testListFoodItems(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/fooditems');
        
        $response = $client->getResponse();
        $responseContent = $response->getContent();

        // Debugging statements
      //  error_log('Response status code: ' . $response->getStatusCode());
      //  error_log('Response content: ' . $responseContent);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testDeleteFoodItem(): void
    {
        $client = static::createClient();
        // First, add a food item to delete
        $client->request(
            'POST',
            '/api/fooditems/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                "name" => "Banana",
                "type" => "fruit",
                "quantity" => 1000,
                "unit" => "kg"
            ])
        );

        $response = $client->getResponse();
        $responseContent = $response->getContent();

        $foodItem = json_decode($responseContent, true);
        $this->assertArrayHasKey('id', $foodItem);

        $foodItemId = $foodItem['id'];

        $client->request('DELETE', '/api/fooditems/remove/' . $foodItemId);

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('FoodItem deleted successfully', $response->getContent());
    }

        public function testSearchFoodItemsByType(): void
        {
            $client = static::createClient();
            $client->request('GET', '/api/fooditems/search/fruit');

            $response = $client->getResponse();
            $responseContent = $response->getContent();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($responseContent);

            $data = json_decode($responseContent, true);

            $this->assertArrayHasKey('fruit', $data);
        }

        public function testAddFoodItemWithInvalidQuantity()
        {
            $client = static::createClient();
            $client->request(
                'POST',
                '/api/fooditems/add',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    "name" => "Banana",
                    "type" => "fruit",
                    "quantity" => "two thousand",
                    "unit" => "kg"
                ])
            );

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        }

        public function testAddItemsByTypeWithValidType()
    {
        $client = static::createClient();
        $data = json_encode([
            ['name' => 'Strawberry', 'quantity' => 20, 'unit' => 'g']
        ]);

        $client->request(
            'POST',
            '/api/fooditems/add/fruit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
    }

    public function testAddItemsByTypeWithValidationErrors()
    {
        $client = static::createClient();
        $data = json_encode([
            ['name' => '', 'quantity' => -1, 'unit' => 'grams']
        ]);

        $client->request(
            'POST',
            '/api/fooditems/add/fruit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data
        );

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
    }

    public function testAddItemsByTypeWithMixedValidity()
    {
        $client = static::createClient();
        $data = json_encode([
            [
                'name' => 'Ashguard',
                'quantity' => '10',
                'unit' => 'g'
            ],
            [
                'name' => 'Soap',
                'unit' => 'kg'
            ]
        ]);

        $client->request(
            'POST',
            '/api/fooditems/add/fruit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data
        );

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        
        $expectedResponse = [
            "processed" => [
                "fruits" => [
                    [
                        "name" => "Ashguard",
                        "type" => "fruit",
                        "quantity" => 10
                    ]
                ],
                "vegetables" => []
            ],
            "errors" => [
                [
                    "index" => 1,
                    "item" => [
                        "name" => "Soap",
                        "unit" => "kg"
                    ],
                    "error" => "Warning: Undefined array key \"quantity\""
                ]
            ],
            "successful" => true
        ];

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse['processed']['fruits'][0]['name'], $responseContent['processed']['fruits'][0]['name']);
        $this->assertEquals($expectedResponse['processed']['fruits'][0]['type'], $responseContent['processed']['fruits'][0]['type']);
        $this->assertEquals($expectedResponse['processed']['fruits'][0]['quantity'], $responseContent['processed']['fruits'][0]['quantity']);
        $this->assertEquals($expectedResponse['errors'][0]['index'], $responseContent['errors'][0]['index']);
        $this->assertEquals($expectedResponse['errors'][0]['item'], $responseContent['errors'][0]['item']);
    }

}