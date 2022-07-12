<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
class DepositDto
{
    #[Serializer\Type("string")]
    public string $username;

    #[Serializer\Type("float")]
    public float $amount;
}