becklyn/ddd-doctrine-bridge provides Doctrine ORM implementations for the event store and transaction manager interfaces found in becklyn/ddd-core. The library is independent from any technology platform other than Doctrine, but we also provide the becklyn/ddd-symfony-bridge library for use within a Symfony application.

## How To

See becklyn/ddd-core documentation for how to use the components provided by the libraries. 

### Setting Up the Event Store

Without using the becklyn/ddd-symfony-bridge library you will have to integrate the event store with your application yourself. Aside from the event store implementation, this library provides Doctrine ORM mappings for it in both XML and annotation formats, as well as a Doctrine Migrations 3 migration to set up the database tables.

### Microseconds in Event Timestamps

To have Doctrine ORM 2 persist microseconds in the database as part of the timestamp representing when an event has been raised, you need to register the `DateTimeImmutableMicrosecondsType` class with Doctrine DBAL as an override for the `datetime_immutable` type. It should ideally be done during bootstrapping, for example:
```
use Doctrine\DBAL\Types\Type;
Type::overrideType('datetime_immutable', 'Becklyn\Ddd\DateTime\Infrastructure\Doctrine\DateTimeImmutableMicrosecondsType'); 
```
This should no longer be necessary with Doctrine ORM 3 as it should incorporate this feature natively.