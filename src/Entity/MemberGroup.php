<?php

namespace src\Entity;

use App\Entity\Company;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class MemberGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'memberGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\Column(updatable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $externalId;

    #[ORM\Column(updatable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column]
    private bool $active = false;

    #[ORM\Column(updatable: false)]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, MemberGroupSubscription> */
    #[
        ORM\OneToMany(mappedBy: 'group', targetEntity: MemberGroupSubscription::class),
        ORM\OrderBy(["createdAt" => "ASC"])
    ]
    private Collection $subscriptions;

    /** @var Collection<int, Member> */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Member::class)]
    private Collection $members;

    /**
     * @throws Exception
     */
    public function __construct(
        Company $company,
        string $externalId,
        string $email,
    ) {
        $this->company = $company;
        $this->externalId = $externalId;
        $this->email = $email;
        $this->subscriptions = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->activate();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function updateEmail(string $email): MemberGroup
    {
        $this->email = $email;

        return $this;
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
     * @return Collection<int, MemberGroupSubscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function getActiveSubscription(): ?MemberGroupSubscription
    {
        $subscription = $this->subscriptions->last();

        return ($subscription && $subscription->isSubscriptionActive()) ? $subscription : null;
    }

    public function getLastSubscription(): ?MemberGroupSubscription
    {
        return $this->subscriptions->last() ?: null;
    }

    public function getPrimaryMember(): ?Member
    {
        return $this->members->findFirst(
            function (int $key, Member $member) {
                return $member->isPrimary();
            }
        );
    }

    public function getActivePrimaryMember(): ?Member
    {
        return $this->members->findFirst(
            function (int $key, Member $member) {
                return $member->isPrimary() && $member->isActive();
            }
        );
    }

    public function getSpouse(): ?Member
    {
        return $this->members->findFirst(
            function (int $key, Member $member) {
                return $member->isSpouse() && $member->isActive();
            }
        );
    }

    /**
     * @return iterable<int, Member>
     */
    public function getDependents(): iterable
    {
        return $this->members->filter(
            function (Member $member) {
                return !$member->isPrimary() && $member->isActive();
            }
        );
    }

    /**
     * @return iterable<int, Member>
     */
    public function getAdultChildren(): iterable
    {
        return $this->members->filter(
            function (Member $member) {
                return $member->isAdultChild() && $member->isActive();
            }
        );
    }

    /**
     * @return iterable<int, Member>
     */
    public function getMinorChildren(): iterable
    {
        return $this->members->filter(
            function (Member $member) {
                return $member->isMinorChild() && $member->isActive();
            }
        );
    }

    /**
     * @return Collection<int, Member>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * @throws Exception
     */
    public function activate(): void
    {
        if ($this->getPrimaryMember()) {
            $this->active = true;

            foreach ($this->getMembers() as $member) {
                $member->activate();
            }
        }
    }

    public function deactivate(): void
    {
        $this->active = false;

        foreach ($this->getMembers() as $member) {
            $member->deactivate();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasActiveMember(): bool
    {
        return $this->getMembers()->findFirst(fn (int $key, Member $member) => $member->isActive()) !== null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMember(string $firstname, string $lastname, DateTimeImmutable $birthday): ?Member
    {
        return $this->getMembers()->findFirst(
            function (int $key, Member $member) use ($firstname, $lastname, $birthday) {
                return $member->isActive()
                    && $member->getFirstname() === $firstname
                    && $member->getLastname() === $lastname
                    && $member->getBirthday()->format('Y-m-d') == $birthday->format('Y-m-d') ;
            }
        );
    }

    public function addMember(Member $member): self
    {
        $this->members[] = $member;

        return $this;
    }
}
