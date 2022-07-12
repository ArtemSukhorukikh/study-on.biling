<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Dto\Transformers\CourseResponseDTOTransformer;
use App\Repository\CourseRepository;

use function PHPSTORM_META\map;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    public CourseResponseDTOTransformer $responseDTOTransformer;
    public PaymentService $paymentService;
    public function __construct(CourseResponseDTOTransformer $responseDTOTransformer,
                                PaymentService $paymentService,
                                )
    {
        $this->responseDTOTransformer = $responseDTOTransformer;
        $this->paymentService = $paymentService;
    }
    #[Route('/', name: 'app_course_all', methods: ['GET'])]
    public function findAll(CourseRepository $courseRepository): Response
    {
        return $this->json($this->responseDTOTransformer->transformFromObjects($courseRepository->findAll()), Response::HTTP_OK);
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