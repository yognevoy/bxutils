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

        self::fetch($code);

        if (empty(self::$iblocks[$code])) {
            throw new IBlockNotFoundException($code);
        }
        return self::$iblocks[$code];
    }

    /**
     * @param string $code
     * @return void
     */
    public static function fetch(string $code): void
    {
        if (!isset(self::$iblocks[$code])) {
            self::$iblocks[$code] = IblockTable::getList([
                'filter' => ['=CODE' => $code],
                'select' => ['ID']
            ])->fetch()['ID'];
        }
    }
}
