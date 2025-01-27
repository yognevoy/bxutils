<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Main\UserFieldTable;

class UserField
{
    const KEY_VALUE = 'VALUE';
    const KEY_XML_ID = 'XML_ID';

    static array $codeMap = [];

    /**
     * Returns a list of values for user field with the list type.
     *
     * @param $fieldId - field ID
     * @param string $entityId - entity UF_ID
     * @param string $key - поле, значение которого будет записано в ключ элементов массива. XML_ID | VALUE
     * @return array
     */
    public static function getEnumValues($fieldId, string $entityId, string $key): array
    {
        if (!is_array($fieldId)) {
            $fieldId = [$fieldId];
        }

        self::fetch($fieldId, $entityId);

        $result = [];
        foreach ($fieldId as $id) {
            foreach (self::$codeMap[$entityId][$id] as $field) {
                $result[$id][$field[$key]] = $field['ID'];
            }
        }
        if (count($result) <= 1) {
            $result = current($result);
        }
        return $result;
    }

    /**
     * @param array $fieldIds
     * @param string $entityId
     * @return void
     */
    private static function fetch(array $fieldIds, string $entityId): void
    {
        $arFields = UserFieldTable::getList([
            'filter' => [
                '=ENTITY_ID' => $entityId,
                '=FIELD_NAME' => $fieldIds
            ],
            'select' => [
                'ID',
                'FIELD_NAME'
            ]
        ])->fetchAll();

        foreach ($arFields as $field) {
            if (self::$codeMap[$entityId][$field['FIELD_NAME']]) {
                return;
            }
            self::$codeMap[$entityId][$field['FIELD_NAME']] = [];

            $dbResult = \CUserFieldEnum::GetList([], [
                'USER_FIELD_ID' => $field['ID']
            ]);
            while ($res = $dbResult->fetch()) {
                self::$codeMap[$entityId][$field['FIELD_NAME']][] = $res;
            }
        }
    }
}
