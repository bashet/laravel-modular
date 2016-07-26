<?php

namespace Tests\Console\Commands;

use ArrayAccess;
use Mnabialek\LaravelModular\Console\Commands\ModuleMakeMigration;

use Mnabialek\LaravelModular\Models\Module;
use Mnabialek\LaravelModular\Services\Config;
use Tests\UnitTestCase;
use Mockery as m;

class ModuleMakeMigrationTest extends UnitTestCase
{
    /** @test */
    public function it_displays_error_when_type_without_table()
    {
        $command = m::mock(ModuleMakeMigration::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('verifyConfigExistence')->once()
            ->andReturn(null);

        $command->shouldReceive('proceed')->once()->withNoArgs()->passthru();

        $command->shouldReceive('argument')->once()->with('module');
        $command->shouldReceive('argument')->once()->with('name')->once();
        $command->shouldReceive('option')->once()->with('type')->once()
            ->andReturn('type value');
        $command->shouldReceive('option')->once()->with('table')->once();

        $command->shouldReceive('error')->once()
            ->with('You need to use both options --type and --table when using any of them');

        $command->shouldNotReceive('verifyExisting');

        $command->handle();
    }

    /** @test */
    public function it_displays_error_when_table_without_type()
    {
        $command = m::mock(ModuleMakeMigration::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('verifyConfigExistence')->once()
            ->andReturn(null);

        $command->shouldReceive('proceed')->once()->withNoArgs()->passthru();

        $command->shouldReceive('argument')->once()->with('module');
        $command->shouldReceive('argument')->once()->with('name')->once();
        $command->shouldReceive('option')->once()->with('type')->once();
        $command->shouldReceive('option')->once()->with('table')->once()
            ->andReturn('table value');

        $command->shouldReceive('error')->once()
            ->with('You need to use both options --type and --table when using any of them');

        $command->shouldNotReceive('verifyExisting');

        $command->handle();
    }

    /** @test */
    public function it_displays_error_when_no_stub_file_in_config()
    {
        $app = m::mock(ApplicationClass::class);

        $command = m::mock(ModuleMakeMigration::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $command->setLaravel($app);

        $command->shouldReceive('verifyConfigExistence')->once()
            ->andReturn(null);

        $command->shouldReceive('proceed')->once()->withNoArgs()->passthru();

        $command->shouldReceive('argument')->once()->with('module')
            ->andReturn('A');
        $command->shouldReceive('argument')->once()->with('name')->once()
            ->andReturn('sample name');
        $command->shouldReceive('option')->once()->with('type')->once();
        $command->shouldReceive('option')->once()->with('table')->once();

        $moduleAMock = m::mock(Module::class);

        $modules = collect([$moduleAMock]);

        $command->shouldReceive('verifyExisting')->once()
            ->with(m::on(function ($arg) use ($moduleAMock) {
                return $arg->first() == 'A';
            }))->andReturn($modules);

        $command->shouldReceive('createMigrationFile')->once()
            ->with($moduleAMock, 'sample name', null, null)->passthru();

        $command->shouldReceive('getStubGroup')->once()
            ->andReturn('sample stub group');

        $config = m::mock(Config::class);

        $app->shouldReceive('offsetGet')->times(2)->with('modular.config')
            ->andReturn($config);
        $config->shouldReceive('getMigrationDefaultType')->once()
            ->andReturn('sample type');
        $config->shouldReceive('getMigrationStubFileName')->once()
            ->with('sample type')->andReturn(null);

        $command->shouldReceive('error')->once()
            ->with('There is no sample type in module_migrations.types registered in configuration file');

        $command->handle();
    }

    /** @test */
    public function it_generates_migration_when_no_errors()
    {
        $app = m::mock(ApplicationClass::class);

        $command = m::mock(ModuleMakeMigration::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $command->setLaravel($app);

        $command->shouldReceive('verifyConfigExistence')->once()
            ->andReturn(null);

        $tableName = 'sample table';
        $userMigrationName = 'sample name';
        $migrationType = 'sample type';
        $moduleName = 'ModuleA';

        $command->shouldReceive('proceed')->once()->withNoArgs()->passthru();

        $command->shouldReceive('argument')->once()->with('module')
            ->andReturn($moduleName);
        $command->shouldReceive('argument')->once()->with('name')->once()
            ->andReturn($userMigrationName);
        $command->shouldReceive('option')->once()->with('type')->once()
            ->andReturn($migrationType);
        $command->shouldReceive('option')->once()->with('table')->once()
            ->andReturn($tableName);

        $moduleAMock = m::mock(Module::class);
        $moduleAMock->shouldReceive('getName')->times(2)->withNoArgs()
            ->andReturn($moduleName);

        $modules = collect([$moduleAMock]);

        $command->shouldReceive('verifyExisting')->once()
            ->with(m::on(function ($arg) use ($moduleAMock, $moduleName) {
                return $arg->first() == $moduleName;
            }))->andReturn($modules);

        $command->shouldReceive('createMigrationFile')->once()
            ->with(m::on(function ($arg) use ($moduleName) {
                return $arg instanceof Module && $arg->getName() == $moduleName;
            }), $userMigrationName, $migrationType, $tableName)->passthru();

        $stubGroupName = 'sample stub group';
        $migrationStubFileName = 'sample stub file';
        $migrationName = 'sample_migration.php';
        $modulePath = 'sample A path';

        $command->shouldReceive('getStubGroup')->once()
            ->andReturn($stubGroupName);

        $config = m::mock(Config::class);

        $app->shouldReceive('offsetGet')->once()->with('modular.config')
            ->andReturn($config);
        $config->shouldNotReceive('getMigrationDefaultType');
        $config->shouldReceive('getMigrationStubFileName')->once()
            ->with($migrationType)->andReturn($migrationStubFileName);

        $command->shouldReceive('getMigrationFileName')->once()
            ->with($userMigrationName)->andReturn($migrationName);

        $migrationClass = studly_case($userMigrationName);

        $moduleAMock->shouldReceive('getMigrationsPath')->once()->withNoArgs()
            ->andReturn($modulePath);

        $command->shouldReceive('copyStubFileIntoModule')->once()
            ->with($moduleAMock, $migrationStubFileName, $stubGroupName,
                $modulePath . DIRECTORY_SEPARATOR . $migrationName,
                ['migrationClass' => $migrationClass, 'table' => $tableName]);

        $command->shouldReceive('info')->once()
            ->with('[Module ' . $moduleName . '] Created migration file: ' .
                $migrationName);

        $command->handle();
    }
}

class ApplicationClass implements ArrayAccess
{

    public function offsetExists($offset)
    {
    }

    public function offsetGet($offset)
    {
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}