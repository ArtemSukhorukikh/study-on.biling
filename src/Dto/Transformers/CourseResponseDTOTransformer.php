<?php
namespace App\Dto\Transformers;

use App\Dto\CourseDto;
use App\Dto\Transformers\AbstractResponceDTOTransformer;

class CourseResponseDTOTransformer extends AbstractResponceDTOTransformer
{
    public function transformFromObject($object)
    {
        $dto = new CourseDto();
        $dto->code = $object->getCode();
        if($object->getType() == 0) {
            $dto->type = "free";
        }
        else if($object->getType() == 1) {
            $dto->type = "rent";
            $dto->price = $object->getPrice();
        } 
        else {
            $dto->type = "buy";
            $dto->price = $object->getPrice();
        }
        return $dto;
    }
} 