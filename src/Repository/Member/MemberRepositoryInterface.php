<?php

declare(strict_types=1);

namespace src\Repository\Member;

use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use src\Entity\Member;
use src\Exception\Validation\ValidationException;

interface MemberRepositoryInterface
{
    public function findOrFail(int $id): Member;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?Member;

    /**
     * @throws ValidationException
     */
    public function findOneByOrFail(array $criteria, ?array $orderBy = null): Member;

    public function update(Member $member): void;

    /**
     * @throws NonUniqueResultException
     */
    public function getActiveMemberByCustomerId(int $customerId): ?Member;

    public function findAllBy(int $companyId, int $firstResult, int $maxResult): Paginator;

    public function findMemberGroupByOrFail(int $companyId, string $externalId): array;

    public function findByEmailAndName(
        string $email,
        string $firstname,
        string $lastname,
        DateTimeImmutable $birthday,
    ): ?Member;

    public function delete(Member $member): void;
}
