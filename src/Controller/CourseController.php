<?php

namespace App\Controller;

use App\Dto\CourseNewDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Dto\Transformers\CourseResponseDTOTransformer;
use App\Repository\CourseRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    public CourseResponseDTOTransformer $responseDTOTransformer;
    protected $serializer;
    public PaymentService $paymentService;
    private $validator;
    public function __construct(CourseResponseDTOTransformer $responseDTOTransformer,
                                PaymentService $paymentService,
                                SerializerInterface $serializer,
                                ValidatorInterface $validator
                                )
    {
        $this->responseDTOTransformer = $responseDTOTransformer;
        $this->paymentService = $paymentService;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
    * @OA\Get(
    *     path="/api/v1/courses",
    *     tags={"Courses"},
    *     summary="Получение всех курсов",
    *     description="Получение всех курсов",
    *     operationId="courses.index",
    *     @OA\Response(
    *          response="200",
    *          description="Успешное получение курсов",
    *          @OA\JsonContent(
    *              type="array",
    *              @OA\Items(
    *                  @OA\Property(
    *                      property="code",
    *                      type="string",
    *                      example="uid1"
    *                  ),
    *                  @OA\Property(
    *                      property="type",
    *                      type="string",
    *                      example="rent"
    *                  ),
    *                  @OA\Property(
    *                      property="price",
    *                      type="number",
    *                      format="float",
    *                      example="150"
    *                  ),
    *              )
    *          )
    *     )
    * )
    */
    #[Route('/', name: 'app_course_all', methods: ['GET'])]
    public function findAll(CourseRepository $courseRepository): Response
    {
        return $this->json($this->responseDTOTransformer->transformFromObjects($courseRepository->findAll()), Response::HTTP_OK);
    }
    /**
    * @OA\Post(
    *     tags={"Courses"},
    *     path="/api/v1/courses/new",
    *     summary="Создание нового курса",
    *     description="Создание нового курса",
    *     operationId="courses.new",
    *     security={
    *         { "Bearer":{} },
    *     },
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(ref="#/components/schemas/CourseDTO")
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="Курс успешно создан",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="201"
    *                 ),
    *                 @OA\Property(
    *                     property="success",
    *                     type="bool",
    *                     example="true"
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=405,
    *         description="Курс с данным кодом уже существует в системе",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="405"
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Курс с данным кодом уже существует в системе"
    *                 ),
    *             ),
    *        )
    *     ),
    * )
    */
    #[Route('/', name: 'app_course_new', methods: ['POST'])]
    public function createNewCourse(CourseRepository $courseRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $courseDto = $this->serializer->deserialize($request->getContent(), CourseNewDto::class, 'json');
        if (!$courseRepository->findOneBy(['code' => $courseDto->code]) && count($this->validator->validate($courseDto)) === 0) {
            $course = new Course();
            if ($courseDto->type === 'free') {
                $course->setType(0);
            }
            elseif ($courseDto->type === 'rent') {
                $course->setType(1);
            } else {
                $course->setType(2);
            }
            $course->setTitle($courseDto->title);
            $course->setCode($courseDto->code);
            $course->setPrice($courseDto->price);
            $entityManager->persist($course);
            $entityManager->flush();
            return $this->json(['success'=> true], Response::HTTP_CREATED);
        }
        return $this->json(['errors' => false], Response::HTTP_BAD_REQUEST);
    }
    /**
    * @OA\Post(
    *     tags={"Courses"},
    *     path="/api/v1/courses/{code}/edit",
    *     summary="Редактирование курса",
    *     description="Редактирование курса",
    *     security={
    *         { "Bearer":{} },
    *     },
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(ref="#/components/schemas/CourseDTO")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Курс изменен",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="200"
    *                 ),
    *                 @OA\Property(
    *                     property="success",
    *                     type="bool",
    *                     example="true"
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=405,
    *         description="Курс с данным кодом уже существует в системе",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="405"
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Курс с данным кодом уже существует в системе"
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Данный курс в системе не найден",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="404"
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Данный курс в системе не найден"
    *                 ),
    *             ),
    *        )
    *     )
    * )
    */
    #[Route('/{code}', name: 'app_course_edit', methods: ['POST'])]
    public function courseEdit(string $code, CourseRepository $courseRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if ($course) {
           $courseDto = $this->serializer->deserialize($request->getContent(),CourseNewDto::class, 'json');
           if ((!$courseRepository->findOneBy(['code' => $courseDto->code]) || $courseDto->code === $code) && count($this->validator->validate($courseDto)) === 0) {
               if ($courseDto->type === 'free') {
                   $course->setType(0);
               }
               elseif ($courseDto->type === 'rent') {
                   $course->setType(1);
               } else {
                   $course->setType(2);
               }
               $course->setTitle($courseDto->title);
               $course->setCode($courseDto->code);
               $course->setPrice($courseDto->price);
               $entityManager->persist($course);
               $entityManager->flush();
               return $this->json(['success'=> true], Response::HTTP_CREATED);
           }
           return $this->json(['errors' => true], Response::HTTP_BAD_REQUEST);
        }
        return $this->json(['errors' => true], Response::HTTP_BAD_REQUEST);
    }
    /**
    * @OA\Get(
    *     path="/api/v1/courses/{code}",
    *     tags={"Courses"},
    *     summary="Получение данного курса",
    *     description="Получение данного курса",
    *     operationId="courses.show",
    *     @OA\Response(
    *         response=200,
    *         description="Курс получен",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="uid1",
    *                 ),
    *                 @OA\Property(
    *                     property="type",
    *                     type="string",
    *                     example="rent",
    *                 ),
    *                 @OA\Property(
    *                     property="price",
    *                     type="number",
    *                     example="150",
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Данный курс не найден",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="404"
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Данный курс не найден"
    *                 ),
    *             ),
    *        )
    *     ),
    * )
    */
    #[Route('/{code}', name: 'app_course_one', methods: ['GET'])]
    public function findOne(string $code, CourseRepository $courseRepository): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if ($course) {
            return $this->json($this->responseDTOTransformer->transformFromObject($course), Response::HTTP_OK);
        }
        return $this->json([], Response::HTTP_OK);
    }
    /**
    * @OA\Post(
    *     tags={"Courses"},
    *     path="/api/v1/courses/{code}/pay",
    *     summary="Оплата курса",
    *     description="Оплата курса",
    *     security={
    *         { "Bearer":{} },
    *     },
    *     @OA\Response(
    *         response=200,
    *         description="Курс куплен",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="success",
    *                     type="boolean",
    *                 ),
    *                 @OA\Property(
    *                     property="course_type",
    *                     type="string",
    *                 ),
    *                 @OA\Property(
    *                     property="expires_at",
    *                     type="string",
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Данный курс не найден",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="404",
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Данный курс не найден",
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=406,
    *         description="У вас недостаточно средств",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="406",
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="На вашем счету недостаточно средств",
    *                 ),
    *             ),
    *        )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Invalid JWT Token",
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="code",
    *                     type="string",
    *                     example="401",
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     example="Invalid JWT Token",
    *                 ),
    *             ),
    *        )
    *     )
    * )
    */
    #[Route('/{code}/pay', name: 'app_course_pay', methods: ['GET'])]
    public function pay(string $code,
                        CourseRepository $courseRepository,
                        UserRepository $userRepository,
                        EntityManagerInterface $entityManager
    ): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if ($course) {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            if ($this->paymentService->payment($course, $user, $entityManager)) {
                $answer = ['success' => true];
                if ($course->getType() === 0) {
                    $answer['course_type'] = 'free';
                } elseif ($course->getType() ===1) {
                    $answer['course_type'] = 'rent';
                    $answer['expires_at'] = (new \DateTime())->modify('next month')->format('c');
                } else {
                    $answer['course_type'] = 'buy';
                }
                return $this->json($answer, Response::HTTP_OK);
            }
            else {
                return $this->json(['code' => 406 , 'message' => 'На вашем счету недостаточно средств'], Response::HTTP_NOT_ACCEPTABLE);
            }
        } else {
            return $this->json(['code' => 406 , 'message' => 'Курс не найден'], Response::HTTP_NOT_ACCEPTABLE);
        }

    }
}