<?php

namespace src\Entity;

use App\Entity\Insurance\UvpPlan;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class MemberGroupSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: MemberGroup::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'member_group_id', nullable: false)]
    #[Assert\Valid]
    private MemberGroup $group;

    #[ORM\ManyToOne(targetEntity: UvpPlan::class)]
    #[ORM\JoinColumn(name: 'plan_id', nullable: false)]
    #[Assert\Valid]
    private UvpPlan $uvpPlan;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true, updatable: true)]
    private ?DateTimeImmutable $endedAt;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: MemberGroupSubscriptionUsage::class)]
    private Collection $usages;

    #[ORM\Column(nullable: true, updatable: true)]
    private ?DateTimeImmutable $notifiedAt;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        MemberGroup $group,
        UvpPlan $uvpPlan,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $endedAt = null,
        ?DateTimeImmutable $notifiedAt = null
    ) {
        $this->group = $group;
        $this->uvpPlan = $uvpPlan;
        $this->startedAt = $startedAt;
        $this->endedAt = $endedAt;
        $this->notifiedAt = $notifiedAt;
        $this->usages = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGroup(): MemberGroup
    {
        return $this->group;
    }

    public function getUvpPlan(): UvpPlan
    {
        return $this->uvpPlan;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function updateEndedAt(?DateTimeImmutable $endedAt = null): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * @return iterable<int, MemberGroupSubscriptionUsage>
     */
    public function getUsages(): iterable
    {
        return $this->usages;
    }

    /**
     * @return iterable<int, MemberGroupSubscriptionUsage>
     */
    public function getMemberUsages(int $memberId): iterable
    {
        return $this->usages->filter(
            function (MemberGroupSubscriptionUsage $usage) use ($memberId) {
                return $usage->getMember()->getId() === $memberId;
            }
        );
    }

    public function isSubscriptionActive(): bool
    {
        $now = new DateTimeImmutable();

        return $this->getStartedAt() <= $now && (is_null($this->getEndedAt()) || $this->getEndedAt() > $now);
    }

    public function addUsage(MemberGroupSubscriptionUsage $usage): self
    {
        $this->usages[] = $usage;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setNotifiedAt(DateTimeImmutable $notifiedAt): void
    {
        $this->notifiedAt = $notifiedAt;
    }

    public function getNotifiedAt(): ?DateTimeImmutable
    {
        return $this->notifiedAt;
    }
}
