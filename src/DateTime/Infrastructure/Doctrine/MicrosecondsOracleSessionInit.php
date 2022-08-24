<?php declare(strict_types=1);

namespace Becklyn\Ddd\DateTime\Infrastructure\Doctrine;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;
use Doctrine\DBAL\Platforms\OraclePlatform;

/**
 * Oracle uses non-standard datetime formats and this is needed to set a PHP-compliant format at the start of each connection
 * Includes microsecond support.
 *
 * @author Marko Vujnovic <mv@becklyn.com>
 *
 * @since  2022-07-12
 */
class MicrosecondsOracleSessionInit extends OracleSessionInit
{
    /** @var string[] */
    protected $_defaultSessionVars = [
        'NLS_TIME_FORMAT' => 'HH24:MI:SS',
        'NLS_DATE_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
        'NLS_TIMESTAMP_FORMAT' => 'YYYY-MM-DD HH24:MI:SS.FF',
        'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS.FF TZH:TZM',
        'NLS_NUMERIC_CHARACTERS' => '.,',
    ];

    public function postConnect(ConnectionEventArgs $args) : void {
        if (!($args->getConnection()->getDatabasePlatform() instanceof OraclePlatform)) {
            return;
        }

        parent::postConnect($args);
    }
}
