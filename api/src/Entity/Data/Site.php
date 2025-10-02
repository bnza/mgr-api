<?php

declare(strict_types=1);

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\Granted\GrantedSiteFilter;
use App\Doctrine\Filter\SearchSiteFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Join\Analysis\AnalysisSiteAnthropology;
use App\Entity\Data\Join\SiteCulturalContext;
use App\State\SitePostProcessor;
use App\Util\EntityOneToManyRelationshipSynchronizer;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(name: 'sites')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
        new Patch(
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:site:create']],
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:site:create']],
            processor: SitePostProcessor::class,
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['site:acl:read']], // <-- ['groups' => ['site:export']] when format is csv @see CsvFormatContextBuilder,
    denormalizationContext: ['groups' => ['site:create']],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'code', 'name', 'chronologyLower', 'chronologyUpper']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'code' => 'exact',
        'culturalContexts.culturalContext' => 'exact',
    ]
)]
#[ApiFilter(
    NumericFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
        'fieldDirector',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper',
        'description',
        'fieldDirector',
        'culturalContexts',
    ]
)]
#[ApiFilter(SearchSiteFilter::class)]
#[ApiFilter(GrantedSiteFilter::class)]
#[UniqueEntity(
    fields: ['code'],
    message: 'Duplicate site code.',
    groups: ['validation:site:create']
)]
#[UniqueEntity(
    fields: ['name'],
    message: 'Duplicate site name.',
    groups: ['validation:site:create']
)]
class Site
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'site:acl:read',
        'site:export',
        'site_user_privilege:acl:read',
        'sample:acl:read',
        'sus:acl:read',
        'sus:export',
    ])]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'context:acl:read',
        'context:export',
        'pottery:acl:read',
        'pottery:export',
        'individual:acl:read',
        'microstratigraphic_unit:acl:read',
        'sample:acl:read',
        'sample:export',
        'sediment_core:acl:read',
        'sediment_core:export',
        'site:acl:read',
        'site:export',
        'site_user_privilege:acl:read',
        'site:create',
        'sus:acl:read',
        'sus:export',
        'context_stratigraphic_unit:acl:read',
        'zoo_bone:acl:read',
        'zoo_tooth:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:site:create',
    ])]
    #[Assert\Regex(
        pattern: '/^[A-Z]{2}[A-Z\d]{0,4}$/',
        message: 'Site code must have up to 6 characters: 2 mandatory uppercase letters followed by up to 4 optional uppercase letters or digits.',
        groups: ['validation:site:create']
    )]
    #[Assert\Length(
        min: 2,
        max: 6,
        minMessage: 'Site code must be 2 or 3 uppercase letters.',
    )]
    private string $code;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'context:acl:read',
        'context:export',
        'individual:acl:read',
        'microstratigraphic_unit:acl:read',
        'pottery:acl:read',
        'pottery:export',
        'sample:acl:read',
        'sample:export',
        'sediment_core:acl:read',
        'sediment_core:export',
        'site:acl:read',
        'site:export',
        'site_user_privilege:acl:read',
        'site:create',
        'sus:acl:read',
        'sus:export',
        'context_stratigraphic_unit:acl:read',
        'zoo_bone:acl:read',
        'zoo_tooth:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:site:create',
    ])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'site:acl:read',
        'site:create',
        'site:export',
    ])]
    private ?string $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdSites')]
    #[ORM\JoinColumn(referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
    ])]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'site:acl:read',
        'site:create',
        'site:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:site:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:site:create'])]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper', groups: ['validation:site:create'])]
    private ?int $chronologyLower = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'site:acl:read',
        'site:create',
        'site:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:site:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:site:create'])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower', groups: ['validation:site:create'])]
    private ?int $chronologyUpper = null;
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'site:acl:read',
        'site:create',
        'site:export',
    ])]
    private ?string $fieldDirector = null;

    #[ORM\OneToMany(
        targetEntity: SiteUserPrivilege::class,
        mappedBy: 'site',
        orphanRemoval: true
    )]
    private Collection $userPrivileges;

    /** @var Collection<SiteCulturalContext> */
    #[ORM\OneToMany(
        targetEntity: SiteCulturalContext::class,
        mappedBy: 'site',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $culturalContexts;

    #[ORM\OneToMany(targetEntity: AnalysisSiteAnthropology::class, mappedBy: 'subject')]
    private Collection $analysesAnthropology;

    private EntityOneToManyRelationshipSynchronizer $culturalContextsSynchronizer;

    public function __construct()
    {
        $this->userPrivileges = new ArrayCollection();
        $this->culturalContexts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Site
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Site
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): Site
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): Site
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Site
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Site
    {
        $this->description = '' === $description ? null : $description;

        return $this;
    }

    public function getUserPrivileges(): Collection
    {
        return $this->userPrivileges;
    }

    public function addUserPrivilege(SiteUserPrivilege $userPrivilege): Site
    {
        if (!$this->userPrivileges->contains($userPrivilege)) {
            $this->userPrivileges->add($userPrivilege);
        }

        return $this;
    }

    public function removeUserPrivilege(SiteUserPrivilege $userPrivilege): Site
    {
        $this->userPrivileges->removeElement($userPrivilege);

        return $this;
    }

    #[Groups([
        'site:acl:read',
    ])]
    public function getCulturalContexts(): Collection
    {
        return $this->culturalContexts->map(function ($siteCulturalContext) {
            return $siteCulturalContext->getCulturalContext();
        });
    }

    public function getAnalysesAnthropology(): Collection
    {
        return $this->analysesAnthropology;
    }

    public function setAnalysesAnthropology(Collection $analysesAnthropology): Site
    {
        $this->analysesAnthropology = $analysesAnthropology;

        return $this;
    }

    private function getCulturalContextsSynchronizer(): EntityOneToManyRelationshipSynchronizer
    {
        if (!isset($this->culturalContextsSynchronizer)) {
            $this->culturalContextsSynchronizer = new EntityOneToManyRelationshipSynchronizer(
                $this->culturalContexts,
                SiteCulturalContext::class,
                'site',
                'culturalContext'
            );
        }

        return $this->culturalContextsSynchronizer;
    }

    #[Groups([
        'site:create',
    ])]
    public function setCulturalContexts(array|Collection $culturalContexts): Site
    {
        if ($culturalContexts instanceof Collection) {
            $this->culturalContexts = $culturalContexts;

            return $this;
        }

        $this->getCulturalContextsSynchronizer()->synchronize($culturalContexts, $this);

        return $this;
    }

    public function getChronologyLower(): ?int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(?int $chronologyLower): Site
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): ?int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(?int $chronologyUpper): Site
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getFieldDirector(): ?string
    {
        return $this->fieldDirector;
    }

    public function setFieldDirector(?string $fieldDirector): Site
    {
        $this->fieldDirector = $fieldDirector;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function refresh(PostPersistEventArgs|PostUpdateEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $entityManager->refresh($this);
    }
}
