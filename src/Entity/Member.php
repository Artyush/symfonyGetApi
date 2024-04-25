<?php

namespace src\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use src\Entity\Enum\Relationship;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Member
{
    const MIN_CHILD_ALLOW_PURCHASE_AGE = 18;

    const MAX_CHILD_ALLOW_PURCHASE_AGE = 25;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: MemberGroup::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'member_group_id', nullable: false)]
    #[Assert\Valid]
    private MemberGroup $group;

    #[ORM\Column(updatable: false)]
    private Relationship $relationship;

    #[ORM\Column(updatable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $firstname;

    #[ORM\Column(updatable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $lastname;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $birthday;

    #[ORM\Column (options: ['default' => false])]
    private bool $active = false;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'member', targetEntity: MemberGroupSubscriptionUsage::class, indexBy: 'id')]
    private Collection $subscriptionUsages;

    /**
     * @throws Exception
     */
    public function __construct(
        MemberGroup $group,
        Relationship $relationship,
        string $firstname,
        string $lastname,
        DateTimeImmutable $birthday,
    ) {
        $this->group = $group;
        $this->relationship = $relationship;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->birthday = $birthday;
        $this->subscriptionUsages = new ArrayCollection();
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

    public function getRelationship(): Relationship
    {
        return $this->relationship;
    }

    public function isPrimary(): bool
    {
        return $this->relationship === Relationship::Self;
    }

    public function isSpouse(): bool
    {
        return $this->relationship === Relationship::Spouse;
    }

    public function isAdultChild(): bool
    {
        return $this->relationship === Relationship::Child
            && (new DateTimeImmutable())->diff($this->birthday)->y >= self::MIN_CHILD_ALLOW_PURCHASE_AGE;
    }

    public function isMinorChild(): bool
    {
        return $this->relationship === Relationship::Child
            && (new DateTimeImmutable())->diff($this->birthday)->y < self::MIN_CHILD_ALLOW_PURCHASE_AGE;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getBirthday(): DateTimeImmutable
    {
        return $this->birthday;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return iterable<int, MemberGroupSubscriptionUsage>
     */
    public function getSubscriptionUsages(): iterable
    {
        return $this->subscriptionUsages;
    }

    public function getUsedPlanQty(): ?int
    {
        $usages = $this->group->getActiveSubscription()?->getMemberUsages($this->getId());

        if(!empty($usages)) {
            $usagesQty = 0;

            foreach ($usages as $usage) {
                $usagesQty += $usage->getQty();
            }

            return $usagesQty;
        }

        return null;
    }

    public function isAllowedToCreateCustomer(): bool
    {
        return $this->relationship !== Relationship::Child
            || (new DateTimeImmutable())->diff($this->birthday)->y >= self::MIN_CHILD_ALLOW_PURCHASE_AGE;
    }

    protected function canBeActivated(): bool
    {
        return $this->relationship !== Relationship::Child
            || (new DateTimeImmutable())->diff($this->birthday)->y <= self::MAX_CHILD_ALLOW_PURCHASE_AGE;
    }
}
