<?php

declare(strict_types=1);

namespace src\Dto;

use DateTimeImmutable;
use src\Entity\Enum\Relationship;

final class GetMembersResultDto
{
    /**
     * @param array<array{
     *     id: int,
     *     customerId: int,
     *     externalId: string,
     *     fullname: string,
     *     email: string,
     *     companyTitle: string,
     *     planTitle: string,
     *     createdAt: DateTimeImmutable,
     *     relationship: Relationship,
     *     birthday: DateTimeImmutable,
     *     active: bool
     *  }> $items
     */
    public function __construct(
        public array $items,
        public int $count
    ) {
        //
    }
}
