<?php

namespace App\Controller;

use App\Dto\UserAuthDto;
use App\Dto\UserDto;
use App\Service\PaymentService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Symfony\Component\Security;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security as SecurityOA;

#[Route('/api/v1')]
class ApiAuthController extends AbstractController
{
    private $serializer;
    private $validator;
    private $passwordHasher;

    public function __construct(ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher)
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @OA\Get(
     *     path="api/v1/users/current",
     *     description="Get current user",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns token user",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Incorrect login and password pair",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="Error message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Unxepected error",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     */
    #[Route('/auth', name: 'api_login', methods: ['POST'])]
    public function login(): Response
    {
        //auth
    }
    /**
     * @OA\Get(
     *     path="api/v1/users/current",
     *     description="Get current user",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns token user",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(type="string")
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="Error message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Unxepected error",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @SecurityOA(name="Bearer")
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTTokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        PaymentService $paymentService,
    ): Response
    {
        $userDto = $this->serializer->deserialize($request->getContent(), UserDto::class, 'json');
        $errors = $this->validator->validate($userDto);
        if ($userRepository->findOneBy(['email' => $userDto->username])) {
            $errors->add(new ConstraintViolation(
                message: 'User ' . $userDto->username .  ' already exists.',
                messageTemplate: 'User {{ value }} already exists.',
                parameters: ['value' => $userDto->username],
                root: $userDto,
                propertyPath: 'username',
                invalidValue: $userDto->username
            ));
        }
        if (count($errors) > 0) {
            $jsonErrors = [];
            foreach ($errors as $error) {
                $jsonErrors[$error->getPropertyPath()][] = $error->getMessage();
            }
            return $this->json([
                'errors' => $jsonErrors,
            ], Response::HTTP_BAD_REQUEST);
        }
        $user = \App\Entity\User::fromDto($userDto, $this->passwordHasher);
        $paymentService->deposit($user, $_ENV['DEPOSIT_START'], $entityManager);
        $entityManager->persist($user);
        $entityManager->flush();
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $refreshTokenManager->save($refreshToken);
        $userAuth = new UserAuthDto();
        $userAuth->roles =  $user->getRoles();
        $userAuth->token = $JWTTokenManager->create($user);
        $userAuth->refresh_token = $refreshToken->getRefreshToken();
        return $this->json($userAuth, Response::HTTP_CREATED);
    }
    #[Route('/token/refresh', name: 'api_refresh_token', methods: ['POST'])]
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
    }
}
