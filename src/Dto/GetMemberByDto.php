<?php

declare(strict_types=1);

namespace src\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetMemberByDto
{
    public function __construct(
        #[Assert\NotBlank, Assert\Length(max: 255)]
        public string $firstname,
        #[Assert\NotBlank, Assert\Length(max: 255)]
        public string $lastname
    ) {
        //
    }
}
