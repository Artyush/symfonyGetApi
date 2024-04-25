<?php

declare(strict_types=1);

namespace src\Service\Member;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use src\Entity\Member;
use src\Entity\Enum\Relationship;
use src\Entity\MemberGroup;
use src\Dto\GetMemberByDto;
use src\Dto\GetMembersResultDto;
use src\Repository\Member\MemberRepositoryInterface;
use src\Exception\Validation\DtoValidationException;
use src\Exception\Validation\ValidationException;
use src\Traits\ValidatingDTO;

final readonly class MemberService implements MemberServiceInterface
{
    use ValidatingDTO;

    public function __construct(
        private MemberRepositoryInterface $memberRepository
    ) {
        //
    }

    /**
     * @throws ValidationException
     */
    public function getOneByOrFail(GetMemberByDto $dto): Member
    {
        return $this->memberRepository->findOneByOrFail([
            'firstname' => $dto->firstname,
            'lastname' => $dto->lastname
        ]);
    }

    public function findByIdOrFail(int $id): Member
    {
        return $this->memberRepository->findOrFail($id);
    }
    /**
     * @param int $companyId
     * @param int|null $firstResult
     * @param int|null $maxResult
     * @return Paginator
     */
    public function findAllBy(int $companyId, $firstResult = 0, $maxResult = 20): Paginator
    {
        return $this->memberRepository->findAllBy($companyId, $firstResult, $maxResult);
    }

    /**
     * @param int $page
     * @param int $maxResult
     * @return int
     */
    public function getFirstResult(int $page = 1, int $maxResult = 20): int
    {
        return $maxResult * ($page - 1);
    }

    /**
     * @param int $companyId
     * @param string $externalId
     * @return array
     */
    public function findMemberGroupByOrFail(int $companyId, string $externalId): array
    {
        return $this->memberRepository->findMemberGroupByOrFail($companyId, $externalId);
    }

    /**
     *  @param array<array{
     *      plan_id: string,
     *      company_id: string,
     *      customer_id: array{from: string, to: string},
     *      fullname: string,
     *      email: string,
     *      member_id: string,
     *      order_by: string,
     *      direction: string,
     *      limit: string,
     *      offset: string
     *  }> $filters
     */
    public function findAllByFilters(array $filters): GetMembersResultDto
    {
        return $this->memberRepository->findAllByFilters($filters);
    }
}
