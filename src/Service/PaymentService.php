<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function deposit(User $user, float $amount, EntityManagerInterface $entityManager) {
        $entityManager->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();
            $transaction->setCreatedAt(new \DateTime());
            $transaction->setType(1);
            $transaction->setValue($amount);
            $transaction->setToUser($user);
            $user->setBalance($user->getBalance() !== null ? $user->getBalance() + $amount : $amount);
            $entityManager->persist($user);
            $entityManager->persist($transaction);
            $entityManager->flush();
            $entityManager->getConnection()->commit();
            return true;
        } catch (\Exception $exception){
            $entityManager->getConnection()->rollBack();
            return false;
        }
    }

    public function payment(Course $course, User $user, EntityManagerInterface $entityManager) {
        $entityManager->getConnection()->beginTransaction();
        try {
            if ($course->getPrice() === null) {
//                $transaction = new Transaction();
//                $transaction->setCreatedAt(new \DateTime());
//                $transaction->setCourse($course);
//                $transaction->setToUser($user);
//                $transaction->setType(0);
//                $transaction->setValue(0);
//                $entityManager->persist($transaction);
//                $entityManager->flush();
//                $entityManager->getConnection()->commit();
                return true;
            }

            if ($user->getBalance() >= $course->getPrice()) {
                $transaction = new Transaction();
                $transaction->setCreatedAt(new \DateTime());
                $transaction->setCourse($course);
                $transaction->setToUser($user);
                $transaction->setType(0);
                if ($course->getType() === 1 || $course->getType() === 2) {
                    $transaction->setValue($course->getPrice());
                    if ($course->getType() == 1) {
                        $transaction->setExpiresAt((new \DateTime())->modify('next month'));
                    }
                    $entityManager->persist($transaction);
                    $entityManager->flush();
                    $user->setBalance($user->getBalance() - $course->getPrice());
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $entityManager->getConnection()->commit();
                    return true;
                }
            } else {
                $entityManager->rollback();
                return false;
            }
        } catch (\Exception $exception) {
            $entityManager->rollback();
            return false;
        }
    }
}