<?php
namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class CourseNewDto
{
    #[Serializer\Type("string")]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public string $type;

    #[Serializer\Type("string")]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public string $title;

    #[Serializer\Type("string")]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public string $code;

    #[Serializer\Type("float")]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public float $price;

}