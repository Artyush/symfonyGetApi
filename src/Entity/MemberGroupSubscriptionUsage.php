<?php

namespace src\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class MemberGroupSubscriptionUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'subscriptionUsages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid]
    private Member $member;

    #[ORM\ManyToOne(targetEntity: MemberGroupSubscription::class, inversedBy: 'usages')]
    #[ORM\JoinColumn(name: 'member_group_subscription_id', nullable: false)]
    #[Assert\Valid]
    private MemberGroupSubscription $subscription;

    #[ORM\Column(updatable: false, options: ['unsigned' => true])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $orderId;

    #[ORM\Column(updatable: false, options: ['unsigned' => true])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $orderItemId;

    #[ORM\Column(updatable: false)]
    private int $qty;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        Member $member,
        MemberGroupSubscription $subscription,
        int $orderId,
        int $orderItemId,
        int $qty
    ) {
        $this->member = $member;
        $this->subscription = $subscription;
        $this->orderId = $orderId;
        $this->orderItemId = $orderItemId;
        $this->qty = $qty;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getSubscription(): MemberGroupSubscription
    {
        return $this->subscription;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getOrderItemId(): int
    {
        return $this->orderItemId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getQty(): int
    {
        return $this->qty;
    }
}

