<?php

namespace Yognevoy\BXUtils\Tests\Utils;

use Mockery;
use PHPUnit\Framework\TestCase;
use Yognevoy\BXUtils\Exception\UserNotFoundException;
use Yognevoy\BXUtils\Utils\UserUtils;

class UserUtilsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetFullNameReturnsFullNameWhenUserExists()
    {
        $userId = 1;

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['ID' => $userId],
                'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
            ])
            ->andReturn(['NAME' => 'John', 'LAST_NAME' => 'Doe', 'LOGIN' => 'johndoe']);

        $fullName = UserUtils::getFullName($userId);

        $this->assertEquals('John Doe', $fullName);
    }

    public function testGetFullNameThrowsExceptionWhenUserNotFound()
    {
        $userId = 2;

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['ID' => $userId],
                'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
            ])
            ->andReturn(null);

        $this->expectException(UserNotFoundException::class);

        UserUtils::getFullName($userId);
    }

    public function testIsInGroupReturnsTrueForUserInNumericGroup()
    {
        $userId = 1;
        $groupId = 2;

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getUserGroupIds')
            ->once()
            ->with($userId)
            ->andReturn([$groupId, 3, 4]);

        $isInGroup = UserUtils::isInGroup($userId, $groupId);

        $this->assertTrue($isInGroup);
    }

    public function testIsInGroupReturnsFalseForUserNotInNumericGroup()
    {
        $userId = 1;
        $groupId = 2;

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getUserGroupIds')
            ->once()
            ->with($userId)
            ->andReturn([3, 4]);

        $isInGroup = UserUtils::isInGroup($userId, $groupId);

        $this->assertFalse($isInGroup);
    }

    public function testIsInGroupReturnsTrueForUserInStringGroup()
    {
        $userId = 1;
        $stringGroupId = 'admin';

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getUserGroupIds')
            ->once()
            ->with($userId)
            ->andReturn([1, 2, 3]);

        $groupTableMock = Mockery::mock('alias:Bitrix\Main\GroupTable');
        $groupTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['STRING_ID' => $stringGroupId],
                'select' => ['ID'],
            ])
            ->andReturn(['ID' => 1]);

        $isInGroup = UserUtils::isInGroup($userId, $stringGroupId);

        $this->assertTrue($isInGroup);
    }

    public function testIsInGroupReturnsFalseForUserNotInStringGroup()
    {
        $userId = 1;
        $stringGroupId = 'admin';

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getUserGroupIds')
            ->once()
            ->with($userId)
            ->andReturn([2, 3]);

        $groupTableMock = Mockery::mock('alias:Bitrix\Main\GroupTable');
        $groupTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['STRING_ID' => $stringGroupId],
                'select' => ['ID'],
            ])
            ->andReturn(['ID' => 1]);

        $isInGroup = UserUtils::isInGroup($userId, $stringGroupId);

        $this->assertFalse($isInGroup);
    }

    public function testIsInGroupReturnsFalseWhenGroupNotFound()
    {
        $userId = 1;
        $stringGroupId = 'NON_EXISTENT_GROUP';

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getUserGroupIds')
            ->once()
            ->with($userId)
            ->andReturn([1, 2, 3]);

        $groupTableMock = Mockery::mock('alias:Bitrix\Main\GroupTable');
        $groupTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['STRING_ID' => $stringGroupId],
                'select' => ['ID'],
            ])
            ->andReturn(null);

        $isInGroup = UserUtils::isInGroup($userId, $stringGroupId);

        $this->assertFalse($isInGroup);
    }

    public function testGetUserGroupsReturnsNonEmptyGroupList()
    {
        $userId = 1;
        $mockedGroups = [
            ['GROUP_ID' => 1],
            ['GROUP_ID' => 2],
            ['GROUP_ID' => 3],
        ];

        $userGroupTableMock = Mockery::mock('alias:Bitrix\Main\UserGroupTable');
        $userGroupTableMock->shouldReceive('getList')
            ->once()
            ->with([
                'filter' => ['=USER_ID' => $userId],
                'select' => ['GROUP_ID'],
            ])
            ->andReturnSelf();

        $userGroupTableMock->shouldReceive('fetchAll')
            ->once()
            ->andReturn($mockedGroups);

        $result = UserUtils::getUserGroups($userId);

        $this->assertSame([1, 2, 3], $result);
    }

    public function testGetUserGroupsReturnsEmptyGroupList()
    {
        $userId = 1;

        $userGroupTableMock = Mockery::mock('alias:Bitrix\Main\UserGroupTable');
        $userGroupTableMock->shouldReceive('getList')
            ->once()
            ->with([
                'filter' => ['=USER_ID' => $userId],
                'select' => ['GROUP_ID'],
            ])
            ->andReturnSelf();

        $userGroupTableMock->shouldReceive('fetchAll')
            ->once()
            ->andReturn([]);

        $result = UserUtils::getUserGroups($userId);

        $this->assertSame([], $result);
    }
}
