<?php

namespace App\Controller;

use App\Dto\DepositDto;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/v1')]
class DepositController extends AbstractController
{
    public SerializerInterface $serializer;
    public PaymentService $paymentService;
    public function __construct(SerializerInterface $serializer, PaymentService $paymentService)
    {
        $this->serializer= $serializer;
        $this->paymentService = $paymentService;
    }

    #[Route('/deposit', name: 'app_deposit', methods: 'POST')]
    public function deposit(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $deposit = $this->serializer->deserialize($request->getContent(), DepositDto::class, 'json');
        if ($this->paymentService->deposit($userRepository->findOneBy(['email' => $deposit->username]), $deposit->amount,$entityManager)) {
            return $this->json('Ok', Response::HTTP_OK);
        }
        return $this->json('Error', Response::HTTP_BAD_REQUEST);
    }
}
