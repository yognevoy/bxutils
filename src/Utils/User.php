<?php

namespace Yognevoy\BXUtils\Utils;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\GroupTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Yognevoy\BXUtils\Exception\UserNotFoundException;

class User
{
    /**
     * Returns user's full name or login.
     *
     * @param int $userId
     * @return string
     * @throws UserNotFoundException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getFullName(int $userId): string
    {
        $user = UserTable::getRow([
            'filter' => [
                'ID' => $userId,
            ],
            'select' => [
                'NAME', 'LAST_NAME', 'LOGIN',
            ],
        ]);

        if (empty($user)) {
            throw new UserNotFoundException();
        }

        if ($user['NAME'] && $user['LAST_NAME']) {
            $name = $user['NAME'] . ' ' . $user['LAST_NAME'];
        } else {
            $name = $user['LOGIN'];
        }

        return $name;
    }

    /**
     * Returns a link to the user's profile.
     *
     * @param int $userId
     * @return string|null
     */
    public static function getProfileUrl(int $userId): ?string
    {
        if (!empty($userId)) {
            return "/company/personal/user/$userId/";
        }
        return null;
    }

    /**
     * Returns a link to the user's avatar.
     *
     * @param int $userId
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getProfilePictureUrl(int $userId): ?string
    {
        if (!empty($userId)) {
            $pictureId = UserTable::getRow([
                'filter' => [
                    '=ID' => $userId,
                ],
                'select' => [
                    'PERSONAL_PHOTO',
                ]
            ])['PERSONAL_PHOTO'];
            if ($pictureId) {
                return \CFile::GetPath($pictureId);
            }
        }
        return null;
    }

    /**
     * Returns user view HTML
     *
     * @param int $userId
     * @return string
     */
    public static function getUserViewHtml(int $userId): string
    {
        global $APPLICATION;
        ob_start();

        $APPLICATION->IncludeComponent('bitrix:main.user.link', '', [
            'CACHE_TYPE' => 'N',
            'CACHE_TIME' => '7200',
            'ID' => $userId,
            'NAME_TEMPLATE' => '#NOBR##LAST_NAME# #NAME##/NOBR#',
            'SHOW_LOGIN' => 'Y',
            'USE_THUMBNAIL_LIST' => 'N',
        ]);

        return ob_get_clean();
    }

    /**
     * Returns true if the user is an Administrator.
     *
     * @param int $userId
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function isAdmin(int $userId): bool
    {
        return self::isInGroup($userId, 1);
    }

    /**
     * Returns true if the user is a member of the group.
     *
     * @param int $userId
     * @param $group
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function isInGroup(int $userId, $group): bool
    {
        $groupIds = UserTable::getUserGroupIds($userId);

        if (is_numeric($group)) {
            $groupId = $group;
        } else {
            $group = GroupTable::getRow([
                'filter' => [
                    'STRING_ID' => $group
                ],
                'select' => [
                    'ID',
                ],
            ]);

            if (!$group) {
                return false;
            }

            $groupId = $group['ID'];
        }

        return in_array($groupId, $groupIds);
    }

    /**
     * Returns user's groups.
     *
     * @param int $userId
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getUserGroups(int $userId): array
    {
        $userGroups = UserGroupTable::getList([
            'filter' => [
                '=USER_ID' => $userId
            ],
            'select' => [
                'GROUP_ID'
            ]
        ])->fetchAll();

        if (empty($userGroups)) {
            return [];
        }

        $groupIds = array_column($userGroups, 'GROUP_ID');
        return array_map('intval', $groupIds);
    }
}
