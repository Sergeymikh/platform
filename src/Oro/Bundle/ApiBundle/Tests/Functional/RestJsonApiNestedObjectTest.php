<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityForNestedObjects as TestEntity;

/**
 * @dbIsolationPerTest
 */
class RestJsonApiNestedObjectTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/nested_object.yml']);
    }

    public function testGet()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityType, 'id' => (string)$entity->getId()])
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertEquals(
            [
                'firstName' => 'first name',
                'lastName'  => 'last name'
            ],
            $result['data']['attributes']['name']
        );
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'name' => [
                        'firstName' => 'first name',
                        'lastName'  => 'last name'
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName' => 'first name',
                'lastName'  => 'last name'
            ],
            $result['data']['attributes']['name']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertEquals('first name', $entity->getFirstName());
        self::assertEquals('last name', $entity->getLastName());
    }

    public function testCreateWithoutNestedObjectData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertNull(
            $result['data']['attributes']['name']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
    }

    public function testUpdate()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name' => [
                        'firstName' => 'new first name',
                        'lastName'  => 'new last name'
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => (string)$entity->getId()]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName' => 'new first name',
                'lastName'  => 'new last name'
            ],
            $result['data']['attributes']['name']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals('new first name', $entity->getFirstName());
        self::assertEquals('new last name', $entity->getLastName());
    }

    public function testUpdateToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name' => null
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => (string)$entity->getId()]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertNull(
            $result['data']['attributes']['name']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
    }

    public function testUpdateWithEmptyArray()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name' => []
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => (string)$entity->getId()]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName' => 'first name',
                'lastName'  => 'last name'
            ],
            $result['data']['attributes']['name']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals('first name', $entity->getFirstName());
        self::assertEquals('last name', $entity->getLastName());
    }
}
