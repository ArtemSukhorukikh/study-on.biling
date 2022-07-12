<?php
namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class TransactionDto
{
    #[Serializer\Type("int")]
    public int $id;

    #[Serializer\Type("string")]
    public string $code;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("float")]
    public float $value;

    #[Serializer\Type("string")]
    public string $created_at;

    #[Serializer\Type("string")]
    public string $expires_at;
}