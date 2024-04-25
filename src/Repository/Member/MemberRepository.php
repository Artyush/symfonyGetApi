<?php

declare(strict_types=1);

namespace src\Repository\Member;

use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use src\Entity\Member;
use src\Entity\MemberGroupSubscription;
use src\Dto\Member\GetMembersResultDto;
use src\Enum\AliasesEnum;
use src\Enum\DirectionEnum;
use src\Enum\Filter\FilterType;
use src\Exception\Validation\ValidationException;
use src\Service\Admin\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MemberRepository implements MemberRepositoryInterface
{
    private const OFFSET_MIN = 0;
    private const LIMIT_MIN = 20;
    private const LIMIT_MAX = 500;

    public function __construct(
        private EntityManagerInterface $em,
        private ConfigurationInterface $configuration
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function findOrFail(int $id): Member
    {
        $entity = $this->em->find(Member::class, $id);
        if (!$entity) {
            throw new ValidationException(
                context: [
                    'className' => Member::class,
                    'id' => $id
                ],
                message: 'Member was not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        return $entity;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?Member
    {
        return $this->em
            ->getUnitOfWork()
            ->getEntityPersister(Member::class)
            ->load(
                criteria: $criteria,
                limit: 1,
                orderBy: $orderBy
            );
    }

    /**
     * @throws ValidationException
     */
    public function findOneByOrFail(array $criteria, ?array $orderBy = null): Member
    {
        $entity = $this->findOneBy($criteria, $orderBy);
        if (!$entity) {
            throw new ValidationException(
                context: [
                    'className' => Member::class,
                    'criteria' => $criteria
                ],
                message: 'Member was not found',
            );
        }

        return $entity;
    }

    public function update(Member $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    public function findAllBy($companyId, int $firstResult = 0, int $maxResult = 20): Paginator
    {
        $query = $this->em
            ->getRepository(Member::class)
            ->createQueryBuilder('m')
            ->join('m.group', 'g', Join::WITH, 'g.id = m.group')
            ->andWhere('g.company = :companyId')
            ->andWhere('m.active = :active')
            ->andWhere('g.active = :active')
            ->setParameters([
                'companyId' => $companyId,
                'active' => 1,
            ])
            ->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResult);

        return new Paginator($query);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getActiveMemberByCustomerId(int $customerId): ?Member
    {
        return $this->em->createQueryBuilder()
                        ->select('m')
                        ->from(Member::class, 'm')
                        ->andWhere('m.customerId = :customerId')
                        ->andWhere('m.active = :active')
                        ->setParameter('customerId', $customerId)
                        ->setParameter('active', 1)
                        ->getQuery()
                        ->getOneOrNullResult()
        ;
    }

    public function findMemberGroupByOrFail(int $companyId, string $externalId): array
    {
        /** @var Member[] $members */
        $members = $this->em
            ->getRepository(Member::class)
            ->createQueryBuilder('m')
            ->join('m.group', 'g', Join::WITH, 'g.id = m.group')
            ->andWhere('g.company = :companyId')
            ->andWhere('g.externalId = :externalId')
            ->andWhere('m.active = :memberActive')
            ->andWhere('g.active = :groupActive')
            ->setParameters([
                'companyId' => $companyId,
                'externalId' => $externalId,
                'memberActive' => 1,
                'groupActive' => 1,
            ])
            ->getQuery()
            ->getResult()
        ;

        if (!$members) {
            throw new NotFoundHttpException('Members not found');
        }

        return $members;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByEmailAndName(
        string $email,
        string $firstname,
        string $lastname,
        DateTimeImmutable $birthday,
    ): ?Member {
        return $this->em->getRepository(Member::class)
                        ->createQueryBuilder('m')
                        ->join('m.group', 'g', Join::WITH, 'g.id = m.group')
                        ->andWhere('g.email = :email')
                        ->andWhere('m.firstname = :firstname')
                        ->andWhere('m.lastname = :lastname')
                        ->andWhere('m.birthday = :birthday')
                        ->andWhere('m.active = :memberActive')
                        ->setParameters([
                            'email' => $email,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'birthday' => $birthday,
                            'memberActive' => true,
                        ])
                        ->getQuery()
                        ->getOneOrNullResult()
        ;
    }

    /**
     * @param array<array{
     *     plan_id: string,
     *     company_id: string,
     *     customer_id: array{from: string, to: string},
     *     fullname: string,
     *     email: string,
     *     member_id: string,
     *     order_by: string,
     *     direction: string,
     *     limit: string,
     *     offset: string
     * }> $filters
     * @throws QueryException
     */
    public function findAllByFilters(array $filters): GetMembersResultDto
    {
        $qb = $this->em
            ->getRepository(Member::class)
            ->createQueryBuilder(AliasesEnum::MEMBER->value);

        $this->applyAllByFiltersSelect($qb);
        $this->applyAllByFiltersJoin($qb);
        $this->applyAllByFiltersCriteria($filters, $qb);

        $offset = isset($filters['offset']) ? (int) $filters['offset'] : self::OFFSET_MIN;
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : self::LIMIT_MIN;
        $this->applyQueryLimits($offset, $limit, $qb);
        if (isset($filters['order_by'])) {
            $this->applyAllByFiltersOrderBy(
                $qb,
                $filters['order_by'],
                $this->getDirection($filters, DirectionEnum::DESC->value)
            );
        }

        return new GetMembersResultDto(
            items: $qb->getQuery()->getResult(),
            count: $this->getAllByFiltersCount($qb)
        );
    }

    private function getDirection(array $filters, string $defaultValue): string
    {
        if (!isset($filters['direction'])) {
            return $defaultValue;
        }

        $direction = $filters['direction'];
        if (!DirectionEnum::tryFrom($direction)) {
            return $defaultValue;
        }

        return $direction;
    }

    private function applyAllByFiltersSelect(QueryBuilder $qb): void
    {
        $memberAlias = AliasesEnum::MEMBER->value;
        $memberGroupAlias = AliasesEnum::MEMBER_GROUP->value;
        $companyAlias = AliasesEnum::COMPANY->value;
        $planAlias = AliasesEnum::PLAN->value;

        $qb
            ->select([
                "$memberAlias.id",
                "$memberAlias.customerId",
                "$memberGroupAlias.externalId",
                "CONCAT($memberAlias.firstname, ' ', $memberAlias.lastname) as fullName",
                "$memberGroupAlias.email",
                "$companyAlias.title as companyTitle",
                "$planAlias.title as planTitle",
                "$memberAlias.createdAt",
                "$memberAlias.relationship",
                "$memberAlias.birthday",
                "$memberAlias.active"
            ]);
    }

    private function applyAllByFiltersJoin(QueryBuilder $qb): void
    {
        $memberAlias = AliasesEnum::MEMBER->value;
        $memberGroupAlias = AliasesEnum::MEMBER_GROUP->value;
        $memberGroupSubscriptionAlias = AliasesEnum::MEMBER_GROUP_SUBSCRIPTION->value;
        $companyAlias = AliasesEnum::COMPANY->value;
        $planAlias = AliasesEnum::PLAN->value;

        $qb
            ->join("$memberAlias.group", $memberGroupAlias)
            ->join("$memberGroupAlias.company", $companyAlias);

        $this->joinActiveSubscription($qb);

        $qb
            ->leftJoin("$memberGroupSubscriptionAlias.uvpPlan", $planAlias);
    }

    private function joinActiveSubscription(QueryBuilder $qb): void
    {
        $memberGroupAlias = AliasesEnum::MEMBER_GROUP->value;
        $memberGroupSubscriptionAlias = AliasesEnum::MEMBER_GROUP_SUBSCRIPTION->value;

        $qb
            ->leftJoin(
                "$memberGroupAlias.subscriptions",
                $memberGroupSubscriptionAlias,
                Join::WITH,
                "$memberGroupSubscriptionAlias.group = $memberGroupAlias.id"
            )
            ->andWhere(
                $qb->expr()->orX(
                    // has no active subscription
                    $qb->expr()->not($qb->expr()->exists($this->getLatestActiveSubscriptionSubQuery('s1'))),
                    // has active subscription
                    $qb->expr()->eq(
                        "$memberGroupSubscriptionAlias.id",
                        '(' . $this->getLatestActiveSubscriptionSubQuery('s2') . ')'
                    )
                )
            );
    }

    private function getLatestActiveSubscriptionSubQuery(string $alias): string
    {
        $memberGroupAlias = AliasesEnum::MEMBER_GROUP->value;

        return $this->em->createQueryBuilder()
            ->select("MAX($alias.id)")
            ->from(MemberGroupSubscription::class, $alias)
            ->where("$alias.group = $memberGroupAlias.id")
            ->andWhere("$alias.startedAt <= CURRENT_TIMESTAMP()")
            ->andWhere("$alias.endedAt IS NULL OR $alias.endedAt > CURRENT_TIMESTAMP()")
            ->groupBy("$alias.id")
            ->getDQL();
    }

    /**
     * @param array<array{
     *     plan_id: string,
     *     company_id: string,
     *     customer_id: array{from: string, to: string},
     *     fullname: string,
     *     email: string,
     *     member_id: string,
     *     order_by: string,
     *     direction: string,
     *     limit: string,
     *     offset: string
     * }> $filters
     * @throws QueryException
     */
    private function applyAllByFiltersCriteria(array $filters, QueryBuilder $qb): void
    {
        $criteria = new Criteria();
        foreach ($filters as $filterName => $filterValue) {
            /** @var FilterType|null $filterType */
            $filterType = $this->configuration->getFilterType($filterName);
            $entityFieldName = $this->configuration->getQueryFieldNameByFilter($filterName);
            $filterType?->applyCriteria($qb, $filterName, $entityFieldName, $filterValue, $criteria);
        }
        $qb->addCriteria($criteria);
    }

    private function applyQueryLimits(
        int $offset,
        int $limit,
        QueryBuilder $qb
    ): void {
        if ($limit > self::LIMIT_MAX) {
            $limit = self::LIMIT_MAX;
        }

        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    private function applyAllByFiltersOrderBy(
        QueryBuilder $qb,
        string $orderBy,
        string $direction
    ): void {
        if ($this->configuration->isSortable($orderBy)) {
            $entityFieldName = $this->configuration->getQueryFieldNameByOrderBy($orderBy);
            $qb->addOrderBy($entityFieldName, $direction);
        }
    }

    private function getAllByFiltersCount(QueryBuilder $qb): int
    {
        $memberAlias = AliasesEnum::MEMBER->value;

        return (clone $qb)
            ->select("COUNT($memberAlias.id)")
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function delete(Member $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }
}
