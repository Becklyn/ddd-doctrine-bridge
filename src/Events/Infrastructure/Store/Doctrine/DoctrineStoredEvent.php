<?php

namespace C201\Ddd\Events\Infrastructure\Store\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Marko Vujnovic <mv@201created.de>
 * @since  2019-08-21
 *
 * @ORM\Entity
 * @ORM\Table(name="event_store")
 * @ORM\HasLifecycleCallbacks()
 */
class DoctrineStoredEvent
{
    /**
     * @ORM\Id
     * @ORM\Column(name="event_id", type="string", length=36)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private string $eventId;

    /**
     * @ORM\ManyToOne(targetEntity="C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventAggregate")
     * @ORM\JoinColumn(name="aggregate_id", referencedColumnName="id")
     */
    private DoctrineStoredEventAggregate $aggregate;

    /**
     * @ORM\Column(name="version", type="integer", nullable=false)
     */
    private int $version;

    /**
     * @ORM\ManyToOne(targetEntity="C201\Ddd\Events\Infrastructure\Store\Doctrine\DoctrineStoredEventType")
     * @ORM\JoinColumn(name="event_type_id", referencedColumnName="id")
     */
    private DoctrineStoredEventType $eventType;

    /**
     * @ORM\Column(name="raised_ts", type="datetime_immutable", nullable=false)
     */
    private \DateTimeImmutable $raisedTs;

    /**
     * @ORM\Column(name="data", type="text", nullable=false)
     */
    private string $data;

    /**
     * @ORM\Column(name="created_ts", type="datetime_immutable", nullable=false)
     */
    private ?\DateTimeImmutable $createdTs = null;

    /**
     * @ORM\PrePersist
     */
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
        string $data
    ) {
        $this->eventId = $eventId;
        $this->aggregate = $aggregate;
        $this->version = $version;
        $this->eventType = $eventType;
        $this->raisedTs = $raisedTs;
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

    public function data(): string
    {
        return $this->data;
    }
}
