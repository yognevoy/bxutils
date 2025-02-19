<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;

class Highload
{
    /**
     * Returns compiled highload-block entity object.
     *
     * @param string $name
     * @return Entity|null
     * @throws ModuleNotIncludedException
     */
    public static function getEntityByName(string $name): ?Entity
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new ModuleNotIncludedException('highloadblock');
        }

        $dbResult = HighloadBlockTable::getRow([
            'filter' => [
                'NAME' => $name
            ]
        ]);

        if ($dbResult) {
            $hlId = $dbResult['ID'];
            $hlBlock = HighloadBlockTable::getById($hlId)->fetch();
            $entity = HighloadBlockTable::compileEntity($hlBlock);
            if ($entity) {
                return $entity;
            }
        }

        return null;
    }
}
