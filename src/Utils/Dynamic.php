<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Loader;
use Yognevoy\BXUtils\Exception\EntityTypeNotFoundException;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;

class Dynamic
{
    static array $entityTypes = [];

    /**
     * Returns entity type id by code.
     *
     * @param string $code
     * @return int
     * @throws EntityTypeNotFoundException
     */
    static function getEntityTypeIdByCode(string $code) : int
    {
        self::getData();
        foreach (self::$entityTypes as $entityType) {
            if ($entityType['CODE'] == $code) {
                return $entityType['ENTITY_TYPE_ID'];
            }
        }
        throw new EntityTypeNotFoundException($code);
    }

    /**
     * Returns entity type by name.
     *
     * @param string $code
     * @return int
     * @throws EntityTypeNotFoundException
     */
    static function getEntityTypeIdByName(string $code) : int
    {
        self::getData();
        foreach (self::$entityTypes as $entityType) {
            if ($entityType['NAME'] == $code) {
                return $entityType['ENTITY_TYPE_ID'];
            }
        }
        throw new EntityTypeNotFoundException($code);
    }

    /**
     * Returns entity data by entity type id.
     *
     * @param int $entityTypeId
     * @return mixed
     * @throws EntityTypeNotFoundException
     */
    public static function getEntityByEntityTypeId(int $entityTypeId): array
    {
        self::getData();
        if (empty(self::$entityTypes[$entityTypeId])) {
            throw new EntityTypeNotFoundException($entityTypeId);
        }
        return self::$entityTypes[$entityTypeId]['ID'];
    }

    /**
     * @return void
     * @throws ModuleNotIncludedException
     */
    private static function getData(): void
    {
        if (!Loader::includeModule('crm')) {
            throw new ModuleNotIncludedException('crm');
        }

        if (empty(self::$entityTypes)) {
            foreach (TypeTable::getList() as $ob) {
                self::$entityTypes[$ob['ENTITY_TYPE_ID']] = $ob;
            }
        }
    }
}
