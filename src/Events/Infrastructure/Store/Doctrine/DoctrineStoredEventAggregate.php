<?php declare(strict_types=1);

namespace Becklyn\Ddd\Events\Infrastructure\Store\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2019-08-21
 */
#[ORM\Entity]
#[ORM\Table(name:"event_store_aggregates")]
#[Orm\HasLifecycleCallbacks]
class DoctrineStoredEventAggregate
{
    #[Orm\Id]
    #[Orm\Column(name: "id", type: "string", length: 36)]
    #[Orm\GeneratedValue(strategy: "NONE")]
    private string $id;

    #[Orm\ManyToOne(targetEntity: DoctrineStoredEventAggregateType::class)]
    #[Orm\JoinColumn(name: "aggregate_type_id", referencedColumnName: "id")]
    private DoctrineStoredEventAggregateType $aggregateType;

    #[Orm\Column(name: "version", type: "integer", nullable: false)]
    private int $version;

    #[Orm\Column(name: "created_ts", type: "datetime_immutable", nullable: false)]
    private ?\DateTimeImmutable $createdTs = null;

    #[Orm\Column(name: "updated_ts", type: "datetime_immutable", nullable: false)]
    private ?\DateTimeImmutable $updatedTs = null;

    #[Orm\PrePersist]
    public function prePersist() : void
    {
        $this->createdTs = new \DateTimeImmutable();
        $this->updatedTs = $this->createdTs;
    }

    #[Orm\PreUpdate]
    public function preUpdate() : void
    {
        $this->updatedTs = new \DateTimeImmutable();
    }

    public function __construct(string $id, DoctrineStoredEventAggregateType $aggregateType, int $version = 0)
    {
        $this->id = $id;
        $this->aggregateType = $aggregateType;
        $this->version = $version;
    }

    public function id() : string
    {
        return $this->id;
    }

    public function aggregateType() : DoctrineStoredEventAggregateType
    {
        return $this->aggregateType;
    }

    public function version() : int
    {
        return $this->version;
    }

    public function incrementVersion() : self
    {
        ++$this->version;
        return $this;
    }
}
