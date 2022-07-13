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
    #[Route('/', name: 'app_course_all', methods: ['GET'])]
    public function findAll(CourseRepository $courseRepository): Response
    {
        return $this->json($this->responseDTOTransformer->transformFromObjects($courseRepository->findAll()), Response::HTTP_OK);
    }

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

    #[Route('/{code}', name: 'app_course_one', methods: ['GET'])]
    public function findOne(string $code, CourseRepository $courseRepository): Response
    {
        $course = $courseRepository->findOneBy(['code' => $code]);
        if ($course) {
            return $this->json($this->responseDTOTransformer->transformFromObject($course), Response::HTTP_OK);
        }
        return $this->json([], Response::HTTP_OK);
    }

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