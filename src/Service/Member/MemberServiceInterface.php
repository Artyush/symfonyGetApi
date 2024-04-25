<?php

declare(strict_types=1);

namespace src\Service\Member;

use Doctrine\ORM\Tools\Pagination\Paginator;
use src\Entity\Member;
use src\Entity\MemberGroup;
use src\Dto\GetMemberByDto;
use src\Dto\GetMembersResultDto;

interface MemberServiceInterface
{
    public function getOneByOrFail(GetMemberByDto $dto): Member;

    public function findByIdOrFail(int $id): Member;

    public function findAllBy(int $companyId, int $firstResult, int $maxResult): Paginator;

    public function findMemberGroupByOrFail(int $companyId, string $externalId): array;

    public function findAllByFilters(array $filters): GetMembersResultDto;
}
