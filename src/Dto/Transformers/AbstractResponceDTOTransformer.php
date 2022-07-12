<?php
namespace App\Dto\Transformers;

abstract class AbstractResponceDTOTransformer implements ResponseDTOTransformerInterface
{
    public function transformFromObjects(iterable $objects): iterable
    {
        $dto = [];

        foreach ($objects as $object) {
            $dto[] = $this->transformFromObject($object);
        }
        return $dto;
    }

}