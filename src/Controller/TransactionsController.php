<?php

namespace App\Controller;

use App\Dto\Transformers\TransactionResponseDTOTransformer;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/transactions')]
class TransactionsController extends AbstractController
{
    public TransactionResponseDTOTransformer $transactionResponseDTOTransformer;

    public function __construct(TransactionResponseDTOTransformer $transactionResponseDTOTransformer)
    {
        $this->transactionResponseDTOTransformer = $transactionResponseDTOTransformer;
    }

    #[Route('', name: 'app_transactions')]
    public function index(Request $request,UserRepository $userRepository, CourseRepository $courseRepository, TransactionRepository $transactionRepository): Response
    {
        $filters = $request->query->all()["filter"] ?? null;
        if ($filters) {
            $query = $transactionRepository
                ->createQueryBuilder('t')
                ->andWhere('t.toUser = :user')
                ->setParameter('user', $userRepository->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]))
                ->orderBy('t.created_at', 'DESC');
            if (isset($filters["type"])) {
                $query->andWhere('t.type = :type')
                    ->setParameter('type', $filters["type"] === "payment" ? 0 :1);
            }
            if (isset($filters["course_code"])) {
                $course = $courseRepository->findOneBy(['code' => $filters["course_code"]]);
                $query->andWhere('t.course = :course')
                    ->setParameter('course', $course?->getId());
            }
            if (isset($filters["skip_expired"])) {
                $query->andWhere('t.expires_at is null or t.expires_at >= :today')
                    ->setParameter('today', new \DateTime());
            }
            return $this->json($this->transactionResponseDTOTransformer->transformFromObjects($query->getQuery()->getResult()));
        }
        return $this->json($this->transactionResponseDTOTransformer->transformFromObjects($transactionRepository->findAll()));
    }
}
