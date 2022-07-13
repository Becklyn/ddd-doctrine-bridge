<?php

namespace Becklyn\Ddd\DateTime\Infrastructure\Doctrine;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Enables microsecond support for \DateTimeImmutable fields mapped to MySQL DATETIME and Oracle TIMESTAMP. The MySQL support should be natively available in
 * Doctrine 3 but Doctrine 2 and Oracle still require this. See readme for instructions.
 *
 * @author Marko Vujnovic <mv@becklyn.com>
 * @since  2019-08-23
 */
class DateTimeImmutableMicrosecondsType extends Type
{
    private const TYPENAME = 'datetime_immutable';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if ($platform instanceof OraclePlatform) {
            return'TIMESTAMP(6)';
        }

        return isset($fieldDeclaration['version']) && $fieldDeclaration['version'] == true ? 'TIMESTAMP' : 'DATETIME(6)';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $value);

        if (!$result) {
            $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', date_create($value)->format('Y-m-d H:i:s.u'));
        }

        if (!$result) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), 'Y-m-d H:i:s.u');
        }

        return $result;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.u');
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', '\DateTimeImmutable']);
    }

    public function getName()
    {
        return self::TYPENAME;
    }
}