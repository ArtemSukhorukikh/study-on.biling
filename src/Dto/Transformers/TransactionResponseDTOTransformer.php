<?php
namespace App\Dto\Transformers;

use App\Dto\CourseDto;
use App\Dto\TransactionDto;
use App\Dto\Transformers\AbstractResponceDTOTransformer;
use App\Entity\Transaction;

class TransactionResponseDTOTransformer extends AbstractResponceDTOTransformer
{
    public function transformFromObject($object)
    {
        $dto = new TransactionDto();
        $dto->id = $object->getId();
        $dto->created_at = $object->getCreatedAt()->format('Y-m-d T H:i:s');
        $dto->type = $object->getType() === 0 ? 'payment' : 'deposit';
        if ($dto->type === 'payment') {
            $dto->code = $object->getCourse()->getId();
        }
        return $dto;
    }
} 