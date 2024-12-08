<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Yognevoy\BXUtils\Exception\IBlockNotFoundException;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;

class IBlock
{
    private static array $iblocks = [];

    /**
     * Returns an iblock id by code.
     *
     * @param string $code
     * @return int
     * @throws IBlockNotFoundException
     * @throws ModuleNotIncludedException
     */
    public static function getIBlockIdByCode(string $code) : int
    {
        if (!Loader::includeModule('iblock')) {
            throw new ModuleNotIncludedException('iblock');
        }

        if (isset(self::$iblocks[$code])) {
            return self::$iblocks[$code];
        }

        self::$iblocks[$code] = IblockTable::getList([
            'filter' => ['=CODE' => $code],
            'select' => ['ID']
        ])->fetch()['ID'];
        if (empty(self::$iblocks[$code])) {
            throw new IBlockNotFoundException($code);
        }
        return self::$iblocks[$code];
    }
}
