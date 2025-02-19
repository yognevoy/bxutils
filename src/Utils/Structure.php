<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CIBlockSection;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;

class Structure
{
    /**
     * Returns users who are members of the department.
     *
     * @param int $departmentId
     * @param bool $recursive - search in sub-departments.
     * @return array
     */
    public static function getDepartmentUsers(int $departmentId, bool $recursive = false): array
    {
        if (!Loader::includeModule('intranet')) {
            throw new ModuleNotIncludedException('intranet');
        }

        $userIds = [];

        $dbResult = \CIntranetUtils::getDepartmentEmployees($departmentId, $recursive);
        while ($res = $dbResult->Fetch()) {
            $userIds[] = (int)$res['ID'];
        }

        return $userIds;
    }

    /**
     * Returns the user's managers.
     *
     * @param int $userId
     * @param bool $recursive - if true, returns all higher-level managers.
     * @return array
     * @throws ArgumentException
     * @throws ModuleNotIncludedException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public static function getUserManagers(int $userId, bool $recursive = false): array
    {
        if (!Loader::includeModule('intranet')) {
            throw new ModuleNotIncludedException('intranet');
        }

        $departments = UserTable::getRow([
            'filter' => [
                'ID' => $userId,
            ],
            'select' => [
                'UF_DEPARTMENT',
            ]
        ])['UF_DEPARTMENT'];

        if (empty($departments)) {
            return [];
        }

        if ($recursive) {
            foreach ($departments as $department) {
                $headDepartments = CIBlockSection::GetNavChain(static::getStructureIBlockID(), $department, ['ID'], true);
                $headDepartments = array_column($headDepartments, 'ID');
                $departments = array_merge($departments, $headDepartments);
            }

            $departments = array_unique($departments);
        }

        $managers = \CIntranetUtils::GetDepartmentManager($departments);

        return array_column($managers, 'ID');
    }

    /**
     * Returns true if the user is the head of at least one department.
     *
     * @param int $userId
     * @return bool
     */
    public static function isDepartmentHead(int $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        $dbRes = CIBlockSection::GetList([],
            [
                'IBLOCK_ID' => static::getStructureIBlockID(),
                'UF_HEAD' => $userId,
            ]
        );

        if ($dbRes->Fetch()) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the user is a member of the department.
     *
     * @param int $userId
     * @param $department
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function isInDepartment(int $userId, $department): bool
    {
        if (empty($userId)) {
            return false;
        }

        $departments = UserTable::getRow([
            'filter' => [
                'ID' => $userId,
            ],
            'select' => [
                'UF_DEPARTMENT',
            ]
        ])['UF_DEPARTMENT'];

        if (empty($departments)) {
            return false;
        }

        if (is_numeric($department)) {
            $departmentId = $department;
        } else {
            $department = SectionTable::getRow([
                'filter' => [
                    'IBLOCK_ID' => static::getStructureIBlockID(),
                    'CODE' => $department,
                ],
                'select' => [
                    'ID',
                ],
            ]);

            if (!$department) {
                return false;
            }

            $departmentId = $department['ID'];
        }

        return in_array($departmentId, $departments);
    }

    /**
     * Returns true if the user has subordinates.
     *
     * @param int $userId
     * @return bool
     */
    public static function hasSubordinates(int $userId): bool
    {
        return !empty(self::getSubordinateIds($userId));
    }

    /**
     * Returns the user's subordinates IDs.
     *
     * @param int $userId
     * @param bool $recursive - if true, returns subordinates of lower-level departments.
     * @return array
     */
    public static function getSubordinateIds(int $userId, bool $recursive = false): array
    {
        if (!Loader::includeModule('intranet')) {
            throw new ModuleNotIncludedException('intranet');
        }

        $employeeIds = [];

        $rsEmployees = \CIntranetUtils::getSubordinateEmployees($userId, $recursive);
        if ($rsEmployees) {
            while ($employee = $rsEmployees->Fetch()) {
                $employeeIds[] = $employee['ID'];
            }
        }

        return $employeeIds;
    }

    /**
     * Returns list of departments.
     *
     * @param int $departmentId - parent department id. If empty, returns all departments.
     * @return array
     */
    public static function getDepartments(int $departmentId = 0): array
    {
        $departments = [];

        $filter = ['IBLOCK_ID' => static::getStructureIBlockID()];

        if (!empty($departmentId)) {
            $dbRes = CIBlockSection::GetList(
                [],
                [
                    'IBLOCK_ID' => static::getStructureIBlockID(),
                    'ID' => $departmentId
                ],
                false,
                ['ID']
            );

            if ($department = $dbRes->fetch()) {
                $filter['SECTION_ID'] = $department['ID'];
            } else {
                return [];
            }
        }

        $select = [
            'ID',
            'NAME',
            'CODE',
            'IBLOCK_SECTION_ID',
            'UF_HEAD'
        ];

        $dbRes = CIBlockSection::GetList(
            [],
            $filter,
            false,
            $select
        );

        while ($res = $dbRes->Fetch()) {
            $departments[] = $res;
        }

        return $departments;
    }

    /**
     * Returns department structure iBlock ID.
     *
     * @return int
     */
    protected static function getStructureIBlockID(): int
    {
        return Option::get('intranet', 'iblock_structure', 0);
    }
}
