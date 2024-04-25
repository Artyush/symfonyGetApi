<?php

declare(strict_types=1);

namespace src\Controller\Api\V1;

use src\Converter\MemberDataToReturnData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use src\Service\Member\MemberServiceInterface;

class GetMemberControllerApi extends AbstractController
{

    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly MemberDataToReturnData $converter
    ) {
    }

    #[OA\Get(
        description: 'Call GET on the /api/v1/members/{companyId} resource. It will return all members collection in the company',
        summary: "Get members collection",
        security: [['Manager' => []]],
        tags: ["Member"],
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Specifies the current page to retrieve",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 1,
                    example: 1
                )
            ),
            new OA\Parameter(
                name: "perPage",
                description: "Sets the number of items per page",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 10,
                    example: 10
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Members collection",
                content: new OA\JsonContent(
                    description: 'Member',
                    properties: [
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "totalPages",
                                    description: "Number of pages",
                                    type: "integer",
                                    example: 10
                                ),
                                new OA\Property(
                                    property: "currentPage",
                                    description: "Specifies the current page to retrieve",
                                    type: "integer",
                                    example: 1
                                ),
                                new OA\Property(
                                    property: "totalItems",
                                    description: "Total number of items available in the dataset",
                                    type: "integer",
                                    example: 100
                                ),
                                new OA\Property(
                                    property: "perPage",
                                    description: "Number of items per page",
                                    type: "integer",
                                    example: 10
                                ),
                                new OA\Property(
                                    property: "Members",
                                    description: "Dataset items",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(
                                                property: "memberId",
                                                description: "Unique member identifier",
                                                type: "string",
                                                maxLength: 255,
                                                example: "0a-1a-2b-3c-4d"
                                            ),
                                            new OA\Property(
                                                property: "firstName",
                                                description: "First name",
                                                type: "string",
                                                maxLength: 255,
                                                example: "John"
                                            ),
                                            new OA\Property(
                                                property: "lastName",
                                                description: "Last name",
                                                type: "string",
                                                maxLength: 255,
                                                example: "Smith"
                                            ),
                                            new OA\Property(
                                                property: "planId",
                                                description: "Membership plan ID",
                                                type: "integer",
                                                example: 1
                                            ),
                                            new OA\Property(
                                                property: "planStartDate",
                                                description: "subscription start date",
                                                type: "data-time",
                                                example: "2021-01-01"
                                            ),
                                            new OA\Property(
                                                property: "planEndDate",
                                                description: "subscription end date",
                                                type: "data-time",
                                                example: "2022-01-01"
                                            ),
                                            new OA\Property(
                                                property: "email",
                                                description: "E-mail address",
                                                type: "string",
                                                maxLength: 255,
                                                example: "smithmail@example.com"
                                            ),
                                            new OA\Property(
                                                property: "birthday",
                                                description: "Member birthday",
                                                type: "data-time",
                                                example: "1990-01-01"
                                            ),
                                            new OA\Property(
                                                property: "relationship",
                                                description: "Member relationship (self, child, spouse)",
                                                type: "string",
                                                enum: ["self", "child", "spouse"],
                                                example: "self"
                                            ),
                                        ],
                                        type: "object"
                                    )
                                )
                            ],
                            type: "object"
                        )
                    ],
                    type: 'object',
                )
            ),
            new OA\Response(
                response: 400,
                description: "Something went wrong",
            ),
            new OA\Response(
                response: 401,
                description: "Expired JWT Token/JWT Token not found"
            )
        ],
    )]
    #[Route(
        path: '/api/v1/members/{companyId}',
        name: 'get-members',
        methods: 'GET'
    )]
    public function __invoke(
        MemberServiceInterface $memberService,
        Request $request,
        int $companyId
    ): JsonResponse {
        $page = max((int) $request->get('page', 1), self::DEFAULT_PAGE);
        $maxResult = ((int)$request->get('perPage') && (int)$request->get('perPage') > 0) ? (int)$request->get('perPage') : self::DEFAULT_PER_PAGE;
        $firstResult = $memberService->getFirstResult($page, $maxResult);

        $members = $memberService->findAllBy($companyId, $firstResult, $maxResult);
        $result = $this->converter->convertAllMembersData($members, $page, $maxResult);

        return $this->json($result, Response::HTTP_OK);
    }

}
