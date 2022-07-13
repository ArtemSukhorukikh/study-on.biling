<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ReportPayment extends Command
{
    private $twig;
    private $mailer;
    private $manager;
    protected static $defaultName = 'payment:report';

    public function __construct(Twig $twig, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->manager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $dataForMail = $this->manager->getRepository(Transaction::class)->forReportForMail();
            if (count($dataForMail) != 0) {
                $endDate = (new \DateTime())->format('Y-m-d');
                $startDate = (new \DateTime())->modify('-1 month')->format('Y-m-d');
                $total = 0;
                foreach ($dataForMail as $data) {
                    $total += $data['sum'];
                }
                $htmlMail = $this->twig->render(
                    'mail/reportMail.html.twig',
                    [
                        'dataForMail' => $dataForMail,
                        'startDate' => $startDate,
                        'endDate'=> $endDate,
                        'total'=>$total,
                    ]
                );
                $message = (new Email())
                    ->to($_ENV['EMAIL_TO_SEND'])
                    ->from('report-system@study-on')
                    ->subject('Отчет')
                    ->html($htmlMail);
                try {
                    $this->mailer->send($message);
                } catch (TransportExceptionInterface $e) {
                    $output->writeln($e->getMessage());
                    $output->writeln('Возникла ошибка. Не удалось отправить сообщение');
                    return Command::FAILURE;
                }
            }
        $output->writeln('Письмо с отчетом отправлено');
        return Command::SUCCESS;
    }
}