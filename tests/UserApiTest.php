<?php

namespace App\Tests;


use App\DataFixtures\UserFixtures;
use App\Dto\UserCurrentDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerBuilder;

class UserApiTest extends AbstractTest
{
    public $serializer;
    public $userRepository;
    public function setUp() :void
    {
        $this->serializer = SerializerBuilder::create()->build();
        //$this->userRepository = self::getEntityManager()->getRepository(User::class);
    }

    public function testAuthWithInvalidDate(): void
    {
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'Admin2@mail.ru','password' => '123qwe'], 'json')
        );
        $this->assertResponseCode(401);
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['code']);
        self::assertNotEmpty($json['message']);
    }

    public function testRegistration(): void
    {
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'uswer2335@mail.com','password' => '123qwe'], 'json')
        );

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    public function testAuth(): void
    {
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'user2335@mail.com','password' => '123qwe'], 'json')
        );

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    public function testCurrentUser(): void
    {
        $user = ['username' => 'user2335@mail.com','password' => '123qwe'];
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize($user, 'json')
        );

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        $token = $json['token'];
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $client = self::getClient();
        $client->request(
            'GET',
             'users/current',
            server: $headers,
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $userDto = $this->serializer->deserialize(
            $client->getResponse()->getContent(),
            UserCurrentDto::class,
            'json'
        );

        //$actualUser = $this->userRepository->findOneBy(['email' => $user['username']]);

        //self::assertEquals($actualUser->getEmail(), $userDto->username);
        //self::assertEquals($actualUser->getRoles(), $userDto->roles);
        //self::assertEquals($actualUser->getBalance(), $userDto->balance);
        self::assertEquals($userDto->username, $user['username']);
    }

    public function testRegistrationInvalid(): void
    {
        $client = AbstractTest::getClient();
        $crawler = $client->request(
            'POST',
            '/api/v1/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'uswer2335','password' => '123qwe'], 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);

        $crawler = $client->request(
            'POST',
            '/api/v1/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->serializer->serialize(['username' => 'uswer2335@mail.com','password' => '123'], 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
    }

    public function getFixtures(): array
    {
        return [UserFixtures::class];
    }
}
