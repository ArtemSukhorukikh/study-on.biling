<?php
namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class CoursesDto
{
    #[Serializer\Type(CourseDto::class)]
    public array $courses;

}