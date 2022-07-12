<?php

namespace App\Dto\Transformers;

interface ResponseDTOTransformerInterface
{
    public function transformFromObject($object);
    public function transformFromObjects(iterable $objects): iterable;

}