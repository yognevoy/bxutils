<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class UserField
{
    static array $codeMap = [];

    /**
     * Returns a list of values for user field with the list type.
     *
     * @param array $userFieldCode - An array of character codes of user fields.
     * @param bool $indexById
     * @param string $entityId
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getUserFieldEnumListByCode(array $userFieldCode, bool $indexById = false, string $entityId = 'empty'): array
    {
        self::fetch($userFieldCode, $entityId);
        $out = [];
        foreach ($userFieldCode as $key) {
            foreach (self::$codeMap[$entityId][$key] as $value) {
                if ($indexById) {
                    $out[$key][$value['ID']] = $value['VALUE'];
                } else {
                    $out[$key][$value['VALUE']] = $value['ID'];
                }
            }
        }
        return $out;
    }

    /**
     * Returns a list of xml id's for user field with the list type.
     *
     * @param array $userFieldCode - An array of character codes of user fields.
     * @param bool $indexById
     * @param string $entityId
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getUserFieldEnumXmlListByCode(array $userFieldCode, bool $indexById = false, string $entityId = 'empty'): array
    {
        self::fetch($userFieldCode, $entityId);
        $out = [];
        foreach ($userFieldCode as $key) {
            foreach (self::$codeMap[$entityId][$key] as $value) {
                if ($indexById) {
                    $out[$key][$value['ID']] = $value['XML_ID'];
                } else {
                    $out[$key][$value['XML_ID']] = $value['ID'];
                }
            }
        }
        return $out;
    }

    /**
     * @param array $userFieldCode
     * @param string $entityId
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function fetch(array $userFieldCode, string $entityId): void
    {
        $userFieldCodeFetch = array_filter($userFieldCode, function($value) use($entityId){
            if (!isset(self::$codeMap[$entityId])) {
                self::$codeMap[$entityId] = [];
            }
            return !array_key_exists($value, self::$codeMap[$entityId]);
        });

        if (!empty($userFieldCodeFetch)) {
            $codeMap = [];
            $out = [];
            $filter = [
                '=FIELD_NAME' => $userFieldCodeFetch
            ];
            if (!empty($entityId) && $entityId != 'empty') {
                $filter['ENTITY_ID'] = $entityId;
            }
            $rs = \Bitrix\Main\UserFieldTable::getList([
                'filter' => $filter
            ]);
            while ($ob = $rs->Fetch()) {
                $codeMap[$ob['ID']] = $ob['FIELD_NAME'];
            }
            if (empty($codeMap)) {
                return;
            }
            $rs = \CUserFieldEnum::GetList(['ENTITY_ID' => 'ASC'], [
                'USER_FIELD_ID' => array_keys($codeMap),
            ]);
            while ($ob = $rs->Fetch()) {
                $code = $codeMap[$ob['USER_FIELD_ID']];
                $out[$code][] = $ob;
            }
            foreach ($out as $key => $value) {
                self::$codeMap[$entityId][$key] = $value;
            }
            unset($out);
        }
    }
}
