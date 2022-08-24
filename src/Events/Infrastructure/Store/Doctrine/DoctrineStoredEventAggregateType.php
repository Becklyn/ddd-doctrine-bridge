<?php declare(strict_types=1);

namespace Becklyn\Ddd\Events\Infrastructure\Store\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2019-08-21
 */
#[ORM\Entity]
#[ORM\Table(name:"event_store_aggregate_types")]
#[ORM\UniqueConstraint(name:"uniq_aggregate_type_name", columns: ["name"])]
#[Orm\HasLifecycleCallbacks]
class DoctrineStoredEventAggregateType
{
    #[Orm\Id]
    #[Orm\Column(name: "id", type: "string", length: 36)]
    #[Orm\GeneratedValue(strategy: "NONE")]
    private string $id;

    #[Orm\Column(name: "name", type: "string", nullable: false)]
    private string $name;

    #[Orm\Column(name: "created_ts", type: "datetime_immutable", nullable: false)]
    private ?\DateTimeImmutable $createdTs = null;

    #[Orm\PrePersist]
    public function prePersist() : void
    {
        $this->createdTs = new \DateTimeImmutable();
    }

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function id() : string
    {
        return $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }
}
