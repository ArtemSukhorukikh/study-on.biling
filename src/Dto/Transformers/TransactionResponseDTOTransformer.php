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
        $dto->value = $object->getValue();
        if ($dto->type === 'payment') {
            $dto->code = $object->getCourse()->getCode();
        }
        if ($object->getExpiresAt()) {
            $dto->expires_at = $object->getExpiresAt()->format('Y-m-d H:i');
        }
        return $dto;
    }
} 