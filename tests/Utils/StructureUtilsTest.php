<?php

namespace Yognevoy\BXUtils\Tests\Utils;

use Mockery;
use PHPUnit\Framework\TestCase;
use Yognevoy\BXUtils\Exception\ModuleNotIncludedException;
use Yognevoy\BXUtils\Utils\StructureUtils;

class TestableStructureUtils extends StructureUtils
{
    protected static function getStructureIBlockID(): int
    {
        return 3;
    }
}

class StructureUtilsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetDepartmentUsersThrowsExceptionWhenModuleNotIncluded()
    {
        $departmentId = 1;

        $loaderMock = Mockery::mock('alias:Bitrix\Main\Loader');
        $loaderMock->shouldReceive('includeModule')
            ->once()
            ->with('intranet')
            ->andReturn(false);

        $this->expectException(ModuleNotIncludedException::class);
        $this->expectExceptionMessage('intranet');

        StructureUtils::getDepartmentUsers($departmentId);
    }

    public function testGetDepartmentUsersReturnsUserIds()
    {
        $departmentId = 10;

        $loaderMock = Mockery::mock('alias:Bitrix\Main\Loader');
        $loaderMock->shouldReceive('includeModule')
            ->once()
            ->with('intranet')
            ->andReturn(true);

        $mockedDBResult = Mockery::mock('\Bitrix\Main\DB\Result');
        $intranetUtilsMock = Mockery::mock('alias:CIntranetUtils');
        $intranetUtilsMock->shouldReceive('getDepartmentEmployees')
            ->once()
            ->with($departmentId, false)
            ->andReturn($mockedDBResult);

        $mockedDBResult->shouldReceive('Fetch')
            ->andReturn(
                ['ID' => 1],
                ['ID' => 2],
                false
            );

        $result = StructureUtils::getDepartmentUsers($departmentId);

        $expected = [1, 2];
        $this->assertSame($expected, $result);
    }

    public function testGetUserManagersReturnsUserIds()
    {
        $userId = 1;

        $loaderMock = Mockery::mock('alias:Bitrix\Main\Loader');
        $loaderMock->shouldReceive('includeModule')
            ->once()
            ->with('intranet')
            ->andReturn(true);

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getRow')
            ->once()
            ->with(['filter' => ['ID' => $userId], 'select' => ['UF_DEPARTMENT']])
            ->andReturn(['UF_DEPARTMENT' => [10]]);

        $intranetUtilsMock = Mockery::mock('alias:CIntranetUtils');
        $intranetUtilsMock->shouldReceive('GetDepartmentManager')
            ->once()
            ->with([10])
            ->andReturn([['ID' => 1], ['ID' => 2]]);

        $result = StructureUtils::getUserManagers($userId);

        $this->assertEquals([1, 2], $result);
    }

    public function testIsDepartmentHeadReturnsTrue()
    {
        $userId = 1;

        $dbResultMock = Mockery::mock();
        $dbResultMock->shouldReceive('Fetch')->once()->andReturn(['ID' => 1]);

        $iblockSectionMock = Mockery::mock('alias:CIBlockSection');
        $iblockSectionMock->shouldReceive('GetList')
            ->once()
            ->with([], ['IBLOCK_ID' => 3, 'UF_HEAD' => $userId])
            ->andReturn($dbResultMock);

        $result = TestableStructureUtils::isDepartmentHead($userId);
        $this->assertTrue($result);
    }

    public function testIsDepartmentHeadReturnsFalse()
    {
        $userId = 123;

        $dbResultMock = Mockery::mock();
        $dbResultMock->shouldReceive('Fetch')->once()->andReturn(false);

        $iblockSectionMock = Mockery::mock('alias:CIBlockSection');
        $iblockSectionMock->shouldReceive('GetList')
            ->once()
            ->with([], ['IBLOCK_ID' => 3, 'UF_HEAD' => $userId])
            ->andReturn($dbResultMock);

        $result = TestableStructureUtils::isDepartmentHead($userId);
        $this->assertFalse($result);
    }

    public function testIsInDepartmentReturnsTrueForUserInDepartment()
    {
        $userId = 123;
        $departmentId = 456;

        $userDepartments = [$departmentId];

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['ID' => $userId],
                'select' => ['UF_DEPARTMENT'],
            ])
            ->andReturn(['UF_DEPARTMENT' => $userDepartments]);

        $result = TestableStructureUtils::isInDepartment($userId, $departmentId);
        $this->assertTrue($result);
    }

    public function testIsInDepartmentReturnsFalseForUserNotInDepartment()
    {
        $userId = 123;
        $departmentId = 456;

        $userDepartments = [789];

        $userTableMock = Mockery::mock('alias:Bitrix\Main\UserTable');
        $userTableMock->shouldReceive('getRow')
            ->once()
            ->with([
                'filter' => ['ID' => $userId],
                'select' => ['UF_DEPARTMENT'],
            ])
            ->andReturn(['UF_DEPARTMENT' => $userDepartments]);

        $result = TestableStructureUtils::isInDepartment($userId, $departmentId);
        $this->assertFalse($result);
    }

    public function testGetSubordinateIdsReturnsSubordinateList()
    {
        $userId = 1;
        $subordinateIds = [456, 789];

        $loaderMock = Mockery::mock('alias:Bitrix\Main\Loader');
        $loaderMock->shouldReceive('includeModule')
            ->once()
            ->with('intranet')
            ->andReturn(true);

        $mockedDBResult = Mockery::mock();
        $mockedDBResult->shouldReceive('Fetch')
            ->andReturn(['ID' => 456])
            ->once()
            ->shouldReceive('Fetch')
            ->andReturn(['ID' => 789])
            ->once()
            ->shouldReceive('Fetch')
            ->andReturn(false);

        $intranetUtilsMock = Mockery::mock('alias:CIntranetUtils');
        $intranetUtilsMock->shouldReceive('getSubordinateEmployees')
            ->once()
            ->with($userId, false)
            ->andReturn($mockedDBResult);

        $result = TestableStructureUtils::getSubordinateIds($userId, false);
        $this->assertSame($subordinateIds, $result);
    }

    public function testGetSubordinateIdsReturnsEmptySubordinateList()
    {
        $userId = 1;

        $loaderMock = Mockery::mock('alias:Bitrix\Main\Loader');
        $loaderMock->shouldReceive('includeModule')
            ->once()
            ->with('intranet')
            ->andReturn(true);

        $mockedDBResult = Mockery::mock();
        $mockedDBResult->shouldReceive('Fetch')
            ->andReturn(false);

        $intranetUtilsMock = Mockery::mock('alias:CIntranetUtils');
        $intranetUtilsMock->shouldReceive('getSubordinateEmployees')
            ->once()
            ->with($userId, false)
            ->andReturn($mockedDBResult);

        $result = TestableStructureUtils::getSubordinateIds($userId, false);
        $this->assertSame([], $result);
    }
}
