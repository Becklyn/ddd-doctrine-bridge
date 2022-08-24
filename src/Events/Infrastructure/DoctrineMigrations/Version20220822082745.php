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
 *
 * @since  2022-08-22
 */
final class Version20220822082745 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Introduces correlation and causation IDs to the event store';
    }

    public function up(Schema $schema) : void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isMySql = $platform instanceof MySQLPlatform;
        $isOracle = $platform instanceof OraclePlatform;
        $isSqlite = $platform instanceof SqlitePlatform;

        $this->skipIf(
            !$isMySql && !$isOracle && !$isSqlite,
            'Migration can only be executed safely on \'MySQL\',  \'SQLite\' or \'Oracle\'.'
        );

        if ($isMySql) {
            $this->addSql("ALTER TABLE event_store ADD correlation_id VARCHAR(36) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000', ADD causation_id VARCHAR(36) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000'");
            $this->addSql('CREATE INDEX correlation_id_idx ON event_store (correlation_id)');
            $this->addSql('CREATE INDEX causation_id_idx ON event_store (causation_id)');
        }

        if ($isOracle) {
            $this->addSql("ALTER TABLE EVENT_STORE ADD (correlation_id VARCHAR2(36) DEFAULT '00000000-0000-0000-0000-000000000000' NOT NULL, causation_id VARCHAR2(36) DEFAULT '00000000-0000-0000-0000-000000000000' NOT NULL)");
            $this->addSql('CREATE INDEX correlation_id_idx ON EVENT_STORE (correlation_id)');
            $this->addSql('CREATE INDEX causation_id_idx ON EVENT_STORE (causation_id)');
        }

        // no case for SQLite because changes to existing tables are simply done in the migration where the table is created
    }

    public function postUp(Schema $schema) : void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isMySql = $platform instanceof MySQLPlatform;
        $isOracle = $platform instanceof OraclePlatform;
        $isSqlite = $platform instanceof SqlitePlatform;

        $this->skipIf(
            !$isMySql && !$isOracle && !$isSqlite,
            'Migration can only be executed safely on \'MySQL\',  \'SQLite\' or \'Oracle\'.'
        );

        if ($isMySql) {
            $this->connection->executeQuery('ALTER TABLE event_store CHANGE correlation_id correlation_id VARCHAR(36) NOT NULL, CHANGE causation_id causation_id VARCHAR(36) NOT NULL');
        }

        if ($isOracle) {
            $this->connection->executeQuery('ALTER TABLE EVENT_STORE MODIFY (correlation_id VARCHAR2(36) DEFAULT NULL, causation_id VARCHAR2(36) DEFAULT NULL)');
        }

        // no case for SQLite because changes to existing tables are simply done in the migration where the table is created
    }

    public function down(Schema $schema) : void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isMySql = $platform instanceof MySQLPlatform;
        $isOracle = $platform instanceof OraclePlatform;
        $isSqlite = $platform instanceof SqlitePlatform;

        $this->skipIf(
            !$isMySql && !$isOracle && !$isSqlite,
            'Migration can only be executed safely on \'MySQL\',  \'SQLite\' or \'Oracle\'.'
        );

        if ($isMySql) {
            $this->addSql('DROP INDEX correlation_id_idx ON event_store');
            $this->addSql('DROP INDEX causation_id_idx ON event_store');
            $this->addSql('ALTER TABLE event_store DROP correlation_id, DROP causation_id');
        }

        if ($isOracle) {
            $this->addSql('DROP INDEX correlation_id_idx');
            $this->addSql('DROP INDEX causation_id_idx');
            $this->addSql('ALTER TABLE event_store DROP (correlation_id, causation_id)');
        }

        // no case for SQLite because changes to existing tables are simply done in the migration where the table is created
    }
}
