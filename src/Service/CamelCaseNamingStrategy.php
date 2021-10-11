<?php

namespace App\Service;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use function strtolower;
use function ucfirst;

class CamelCaseNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName(
        $propertyName,
        $embeddedColumnName,
        $className = null,
        $embeddedClassName = null
    ): string {
        return $propertyName . ucfirst($embeddedColumnName);
    }

    /**
     * {@inheritdoc}
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            return lcfirst(substr($className, strrpos($className, '\\') + 1));
        }
        return lcfirst($className);
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName($propertyName, $className = null): string
    {
        return $propertyName . ucfirst($this->referenceColumnName());
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null): string
    {
        return strtolower($this->classToTableName($sourceEntity) . ucfirst($this->classToTableName($targetEntity)));
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        if (null === $referencedColumnName) {
            $referencedColumnName = $this->referenceColumnName();
        }

        return $this->classToTableName($entityName) . ucfirst($referencedColumnName);
    }
}
