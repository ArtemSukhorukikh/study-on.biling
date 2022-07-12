<?php
namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class CourseDto
{
    #[Serializer\Type("int")]
    public int $id;

    #[Serializer\Type("string")]
    public string $code;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("float")]
    public float $price;

}