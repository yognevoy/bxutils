<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Crm\AddressTable;
use Bitrix\Crm\BankDetailTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Loader;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;

class Requisite
{
    /**
     * Returns the requisites for the entity element.
     *
     * @param int $entityTypeId
     * @param int $entityId
     * @return array|null
     * @throws ModuleNotIncludedException
     */
    public static function getRequisite(int $entityTypeId, int $entityId): ?array
    {
        if (!Loader::includeModule('crm')) {
            throw new ModuleNotIncludedException('crm');
        }

        $rs = RequisiteTable::getList([
            'filter' => [
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_ID' => $entityId
            ]
        ]);
        return $rs->fetch() ?: null;
    }

    /**
     * Returns the bank details for the entity element.
     *
     * @param int $entityTypeId
     * @param int $entityId
     * @return array|null
     * @throws ModuleNotIncludedException
     */
    public static function getBankDetail(int $entityTypeId, int $entityId): ?array
    {
        if (!Loader::includeModule('crm')) {
            throw new ModuleNotIncludedException('crm');
        }

        $rs = BankDetailTable::getList([
            'filter' => [
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_ID' => $entityId
            ]
        ]);
        return $rs->fetch() ?: null;
    }

    /**
     * Returns the address for the entity element.
     *
     * @param int $entityTypeId
     * @param int $entityId
     * @return array|null
     * @throws ModuleNotIncludedException
     */
    public static function getAddress(int $entityTypeId, int $entityId): ?array
    {
        if (!Loader::includeModule('crm')) {
            throw new ModuleNotIncludedException('crm');
        }

        $rs = AddressTable::getList([
            'filter' => [
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_ID' => $entityId
            ]
        ]);
        return $rs->fetch() ?: null;
    }
}
