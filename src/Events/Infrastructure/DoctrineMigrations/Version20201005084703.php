<?php

declare(strict_types=1);

namespace Becklyn\Ddd\Events\Infrastructure\DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2020-10-05
 */
final class Version20201005084703 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds event store tables for use with becklyn/ddd-doctrine-bridge';
    }

    public function up(Schema $schema) : void
    {
        $platform = get_class($this->connection->getDatabasePlatform());
        $this->skipIf(
            !in_array($platform, [SqlitePlatform::class, MySQLPlatform::class, OraclePlatform::class]),
            'Migration can only be executed safely on \'MySQL\',  \'SQLite\' or \'Oracle\'.'
        );

        if ($platform === MySQLPlatform::class) {
            $this->addSql('CREATE TABLE event_store (event_id VARCHAR(36) NOT NULL, aggregate_id VARCHAR(36) DEFAULT NULL, event_type_id VARCHAR(36) DEFAULT NULL, version INT NOT NULL, raised_ts DATETIME(6) NOT NULL, data LONGTEXT NOT NULL, created_ts DATETIME(6) NOT NULL, INDEX IDX_BE4CE95BD0BBCCBE (aggregate_id), INDEX IDX_BE4CE95B401B253C (event_type_id), PRIMARY KEY(event_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE event_store_aggregate_types (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_ts DATETIME(6) NOT NULL, UNIQUE INDEX uniq_aggregate_type_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE event_store_aggregates (id VARCHAR(36) NOT NULL, aggregate_type_id VARCHAR(36) DEFAULT NULL, version INT NOT NULL, created_ts DATETIME(6) NOT NULL, updated_ts DATETIME(6) NOT NULL, INDEX IDX_3E42E1EAC816D3AF (aggregate_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE event_store_event_types (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_ts DATETIME(6) NOT NULL, UNIQUE INDEX uniq_event_type_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE event_store ADD CONSTRAINT FK_BE4CE95BD0BBCCBE FOREIGN KEY (aggregate_id) REFERENCES event_store_aggregates (id)');
            $this->addSql('ALTER TABLE event_store ADD CONSTRAINT FK_BE4CE95B401B253C FOREIGN KEY (event_type_id) REFERENCES event_store_event_types (id)');
            $this->addSql('ALTER TABLE event_store_aggregates ADD CONSTRAINT FK_3E42E1EAC816D3AF FOREIGN KEY (aggregate_type_id) REFERENCES event_store_aggregate_types (id)');
        } else if ($platform === SqlitePlatform::class) {
            $this->addSql('CREATE TABLE event_store (event_id VARCHAR(36) NOT NULL, aggregate_id VARCHAR(36) DEFAULT NULL, event_type_id VARCHAR(36) DEFAULT NULL, version INTEGER NOT NULL, raised_ts DATETIME(6) NOT NULL, data CLOB NOT NULL, created_ts DATETIME(6) NOT NULL, PRIMARY KEY(event_id))');
            $this->addSql('CREATE INDEX IDX_BE4CE95BD0BBCCBE ON event_store (aggregate_id)');
            $this->addSql('CREATE INDEX IDX_BE4CE95B401B253C ON event_store (event_type_id)');
            $this->addSql('CREATE TABLE event_store_aggregate_types (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_ts DATETIME(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX uniq_aggregate_type_name ON event_store_aggregate_types (name)');
            $this->addSql('CREATE TABLE event_store_aggregates (id VARCHAR(36) NOT NULL, aggregate_type_id VARCHAR(36) DEFAULT NULL, version INTEGER NOT NULL, created_ts DATETIME(6) NOT NULL, updated_ts DATETIME(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_3E42E1EAC816D3AF ON event_store_aggregates (aggregate_type_id)');
            $this->addSql('CREATE TABLE event_store_event_types (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, created_ts DATETIME(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX uniq_event_type_name ON event_store_event_types (name)');
        } else {
            $this->addSql('CREATE TABLE event_store (event_id VARCHAR2(36) NOT NULL, aggregate_id VARCHAR2(36) DEFAULT NULL NULL, event_type_id VARCHAR2(36) DEFAULT NULL NULL, version NUMBER(10) NOT NULL, raised_ts TIMESTAMP(6) NOT NULL, data CLOB NOT NULL, created_ts TIMESTAMP(6) NOT NULL, PRIMARY KEY(event_id))');
            $this->addSql('CREATE INDEX IDX_BE4CE95BD0BBCCBE ON event_store (aggregate_id)');
            $this->addSql('CREATE INDEX IDX_BE4CE95B401B253C ON event_store (event_type_id)');
            $this->addSql('CREATE TABLE event_store_aggregate_types (id VARCHAR2(36) NOT NULL, name VARCHAR2(255) NOT NULL, created_ts TIMESTAMP(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX uniq_aggregate_type_name ON event_store_aggregate_types (name)');
            $this->addSql('CREATE TABLE event_store_aggregates (id VARCHAR2(36) NOT NULL, aggregate_type_id VARCHAR2(36) DEFAULT NULL NULL, version NUMBER(10) NOT NULL, created_ts TIMESTAMP(6) NOT NULL, updated_ts TIMESTAMP(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_3E42E1EAC816D3AF ON event_store_aggregates (aggregate_type_id)');
            $this->addSql('CREATE TABLE event_store_event_types (id VARCHAR2(36) NOT NULL, name VARCHAR2(255) NOT NULL, created_ts TIMESTAMP(6) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX uniq_event_type_name ON event_store_event_types (name)');
            $this->addSql('ALTER TABLE event_store ADD CONSTRAINT FK_BE4CE95BD0BBCCBE FOREIGN KEY (aggregate_id) REFERENCES event_store_aggregates (id)');
            $this->addSql('ALTER TABLE event_store ADD CONSTRAINT FK_BE4CE95B401B253C FOREIGN KEY (event_type_id) REFERENCES event_store_event_types (id)');
            $this->addSql('ALTER TABLE event_store_aggregates ADD CONSTRAINT FK_3E42E1EAC816D3AF FOREIGN KEY (aggregate_type_id) REFERENCES event_store_aggregate_types (id)');
        }
    }

    public function down(Schema $schema) : void
    {
        $platform = get_class($this->connection->getDatabasePlatform());
        $this->skipIf(
            !in_array($platform, [SqlitePlatform::class, MySQLPlatform::class, OraclePlatform::class]),
            'Migration can only be executed safely on \'MySQL\',  \'SQLite\' or \'Oracle\'.'
        );

        if ($platform === MySQLPlatform::class) {
            $this->addSql('ALTER TABLE event_store_aggregates DROP FOREIGN KEY FK_3E42E1EAC816D3AF');
            $this->addSql('ALTER TABLE event_store DROP FOREIGN KEY FK_BE4CE95BD0BBCCBE');
            $this->addSql('ALTER TABLE event_store DROP FOREIGN KEY FK_BE4CE95B401B253C');
            $this->addSql('DROP TABLE event_store');
            $this->addSql('DROP TABLE event_store_aggregate_types');
            $this->addSql('DROP TABLE event_store_aggregates');
            $this->addSql('DROP TABLE event_store_event_types');
        } else if ($platform === SqlitePlatform::class) {
            $this->addSql('DROP TABLE event_store');
            $this->addSql('DROP TABLE event_store_aggregate_types');
            $this->addSql('DROP TABLE event_store_aggregates');
            $this->addSql('DROP TABLE event_store_event_types');
        } else {
            $this->addSql('ALTER TABLE event_store_aggregates DROP CONSTRAINT FK_3E42E1EAC816D3AF');
            $this->addSql('ALTER TABLE event_store DROP CONSTRAINT FK_BE4CE95BD0BBCCBE');
            $this->addSql('ALTER TABLE event_store DROP CONSTRAINT FK_BE4CE95B401B253C');
            $this->addSql('DROP TABLE event_store');
            $this->addSql('DROP TABLE event_store_aggregate_types');
            $this->addSql('DROP TABLE event_store_aggregates');
            $this->addSql('DROP TABLE event_store_event_types');
        }
    }
}
