<?php

namespace Becklyn\Ddd\Events\Infrastructure\Store\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-21
 */
#[Orm\Entity]
#[Orm\Table(name: "event_store")]
#[Orm\HasLifecycleCallbacks]
#[Orm\Index(name: "correlation_id_idx", columns: ["correlation_id"])]
#[Orm\Index(name: "causation_id_idx", columns: ["causation_id"])]
class DoctrineStoredEvent
{
    #[Orm\Id]
    #[Orm\Column(name: "event_id", type: "string", length: 36)]
    #[Orm\GeneratedValue(strategy: "NONE")]
    private string $eventId;

    #[Orm\ManyToOne(targetEntity: DoctrineStoredEventAggregate::class)]
    #[Orm\JoinColumn(name: "aggregate_id", referencedColumnName: "id")]
    private DoctrineStoredEventAggregate $aggregate;

    #[Orm\Column(name: "version", type: "integer", nullable: false)]
    private int $version;

    #[Orm\ManyToOne(targetEntity: DoctrineStoredEventType::class)]
    #[Orm\JoinColumn(name: "event_type_id", referencedColumnName: "id")]
    private DoctrineStoredEventType $eventType;

    #[Orm\Column(name: "raised_ts", type: "datetime_immutable", nullable: false)]
    private \DateTimeImmutable $raisedTs;

    #[Orm\Column(name: "correlation_id", type: "string", length: 36, nullable: false)]
    private string $correlationId;

    #[Orm\Column(name: "causation_id", type: "string", length: 36, nullable: false)]
    private string $causationId;

    #[Orm\Column(name: "data", type: "text", nullable: false)]
    private string $data;

    #[Orm\Column(name: "created_ts", type: "datetime_immutable", nullable: false)]
    private ?\DateTimeImmutable $createdTs = null;

    #[Orm\PrePersist]
    public function prePersist(): void
    {
        $this->createdTs = new \DateTimeImmutable();
    }

    public function __construct(
        string $eventId,
        DoctrineStoredEventAggregate $aggregate,
        int $version,
        DoctrineStoredEventType $eventType,
        \DateTimeImmutable $raisedTs,
        string $correlationId,
        string $causationId,
        string $data
    ) {
        $this->eventId = $eventId;
        $this->aggregate = $aggregate;
        $this->version = $version;
        $this->eventType = $eventType;
        $this->raisedTs = $raisedTs;
        $this->correlationId = $correlationId;
        $this->causationId = $causationId;
        $this->data = $data;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function aggregate(): DoctrineStoredEventAggregate
    {
        return $this->aggregate;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function eventType(): DoctrineStoredEventType
    {
        return $this->eventType;
    }

    public function raisedTs(): \DateTimeImmutable
    {
        return $this->raisedTs;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): string
    {
        return $this->causationId;
    }

    public function data(): string
    {
        return $this->data;
    }
}
