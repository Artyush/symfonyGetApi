<?php

declare(strict_types=1);

namespace src\Converter;

use src\Entity\Member;

final class MemberDataToReturnData
{
    public function convertAllMembersData($members, $page, $maxResult): array
    {
        $totalItems = count($members);
        $totalPages = ceil($totalItems / $maxResult);

        return [
            "data" => [
                "totalPages" => $totalPages,
                "currentPage" => $page,
                "totalItems" => $totalItems,
                "perPage" => $maxResult,
                "members" => $this->mappingData($members)
            ]
        ];
    }

    public function convertOneMemberData($members): array
    {
        return [
            "data" => [
                "members" => $this->mappingData($members)
            ]
        ];
    }

    private function mappingData(mixed $entities): array
    {
        $data = [];
        /** @var Member $entity */
        foreach ($entities as $entity) {
            $data[] = [
                'memberId' => $entity->getGroup()->getExternalId(),
                'firstName' => $entity->getFirstname(),
                'lastName' => $entity->getLastname(),
                'planId' => $entity->getGroup()->getActiveSubscription()?->getUvpPlan()->getId(),
                'planStartDate' => $entity->getGroup()->getActiveSubscription()?->getStartedAt()->format('Y-m-d'),
                'planEndDate' => $entity->getGroup()->getActiveSubscription()?->getEndedAt()?->format('Y-m-d'),
                'email' => $entity->getGroup()->getEmail(),
                'birthday' => $entity->getBirthday()->format('Y-m-d'),
                'relationship' => $entity->getRelationship(),
            ];
        }

        return $data;
    }
}
