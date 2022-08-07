<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Dto\CourseNewDto;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;

class CourseTest extends AbstractTest
{
    public $serializer;
    public function setUp() :void
    {
        $this->serializer = SerializerBuilder::create()->build();
    }

    public function auth() {
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'Admin@mail.ru','password' => '123qwe'], 'json')
        );

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        return $json;
    }

    public function testGetAllCourses() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $client->request(
            'GET',
            'courses/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(3, $response);
    }

    public function testGetCourseByCode() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $courseCode = 'uid2';
        $client->request(
            'GET',
            'courses/' . $courseCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals("uid2", $response["code"]);
        self::assertEquals("rent", $response["type"]);
        self::assertEquals(25, $response["price"]);
    }

    public function testGetCourseByInvalidCode() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $courseCode = 'uid21241';
        $client->request(
            'GET',
            'courses/' . $courseCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $response);
    }


    public function testPayCourse() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $courseCode = 'uid2';
        $client->request(
            'POST',
            'deposit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ],
            $this->serializer->serialize(
                ['username' => 'Admin@mail.ru', 'amount' => 25],
                'json'
            )
        );
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());
        $client->request(
            'GET',
            'courses/' . $courseCode . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(true, $response['success']);
    }


//    public function testNewCourse() {
//        $client = AbstractTest::getClient();
//        $userData = $this->auth();
//        $courseNewDTO = new CourseNewDto();
//        $courseNewDTO->code = 'uid47';
//        $courseNewDTO->title = 'Python';
//        $courseNewDTO->type = 'rent';
//        $courseNewDTO->price = 25;
//        $client->request(
//            'POST',
//            'courses/',
//            [],
//            [],
//            [
//                'CONTENT_TYPE' => 'application/json',
//                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
//            ],
//            $this->serializer->serialize($courseNewDTO, 'json')
//        );
//        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());
//        $response = json_decode($client->getResponse()->getContent(), true);
//        self::assertEquals(true, $response['success']);
//    }


    public function testNewInvalidCourse() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $courseNewDTO = new CourseNewDto();
        $courseNewDTO->code = 'uid2';
        $courseNewDTO->title = 'Python';
        $courseNewDTO->type = 'rent';
        $courseNewDTO->price = 25;
        $client->request(
            'POST',
            'courses/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ],
            $this->serializer->serialize($courseNewDTO, 'json')
        );
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());
        $client = self::getClient();
        $courseNewDTO = new CourseNewDto();
        $courseNewDTO->code = 'uid564';
        $courseNewDTO->title = '';
        $courseNewDTO->type = 'rent';
        $courseNewDTO->price = 25;
        $client->back();
        $client->request(
            'POST',
            'courses/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ],
            $this->serializer->serialize($courseNewDTO, 'json')
        );
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());
        $client = self::getClient();
        $courseNewDTO = new CourseNewDto();
        $courseNewDTO->code = 'uid564';
        $courseNewDTO->title = 'Python';
        $courseNewDTO->type = '';
        $courseNewDTO->price = 25;
        $client->back();
        $client->request(
            'POST',
            'courses/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ],
            $this->serializer->serialize($courseNewDTO, 'json')
        );
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

    }


    public function testEditCourse() {
        $client = AbstractTest::getClient();
        $userData = $this->auth();
        $courseNewDTO = new CourseNewDto();
        $courseNewDTO->code = 'uid476';
        $courseNewDTO->title = 'Python2';
        $courseNewDTO->type = 'rent';
        $courseNewDTO->price = 25;
        $courseCode = 'uid2';
        $client->request(
            'POST',
            'courses/' . $courseCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ],
            $this->serializer->serialize($courseNewDTO, 'json')
        );
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(true, $response['success']);
    }


    public function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}