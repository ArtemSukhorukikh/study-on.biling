<?php
namespace App\Dto;

use App\Dto\TransactionDto as DtoTransactionDto;
use JMS\Serializer\Annotation as Serializer;

class TransactionsDto
{
    #[Serializer\Type(TransactionDto::class)]
    public array $transactions;

}