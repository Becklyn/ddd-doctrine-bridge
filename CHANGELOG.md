3.0.1
=======

* (feature) Added support 4.0 series of becklyn/ddd-core

3.0.0
=======

* (bc) Added support for event correlation and causation IDs.
* (feature) Added support for illuminate/collections ^9.0

2.2.2
=====

* (bug) Fixed migration requirements to work as intended.

2.2.1
=====

*   (feature) Added migrations for Oracle

2.2.0
=====

*   (feature) Added \DateTimeImmutable microsecond support for Oracle

2.1.2
=====

*   (improvement) Pins Symfony 5 version to at least `5.4`

2.1.1
=====

*   (improvement) Now works with Symfony 6

2.1.0
=====

*   (feature) Doctrine Migration support for sqlite

1.1.0
=====

*   (feature) Doctrine Migration support for sqlite

2.0.1
=====

*   (bug) Deserializing datetime_immutable values without microseconds from SQL now properly returns DateTimeImmutable in PHP instead of DateTime

2.0.0
=====

*   (feature) PHP8 branch of Doctrine ORM implementations for the event store and transaction manager interfaces found in becklyn/ddd-core
*   (bc) No longer usable with PHP7

1.0.1
=====

*   (bug) Deserializing datetime_immutable values without microseconds from SQL now properly returns DateTimeImmutable in PHP instead of DateTime

1.0.0
=====

*   (feature) PHP7 branch of Doctrine ORM implementations for the event store and transaction manager interfaces found in becklyn/ddd-core
