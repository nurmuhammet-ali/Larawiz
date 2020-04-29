<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class BelongsToManyTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_guesses_target_model_and_creates_pivot_table()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExists($this->app->path('RoleUser.php'));
        $this->assertFileNotExists($this->app->path('UserRole.php'));
        $this->assertFileNotExists(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_user_role_table.php'));

        $userModel = $this->filesystem->get($this->app->path('User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Role.php'));
        $userMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );
        $roleMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_roles_table.php')
        );
        $pivotMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $roles', $userModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Role', $userModel);
        $this->assertStringContainsString('public function roles()', $userModel);
        $this->assertStringContainsString('return $this->belongsToMany(Role::class);', $userModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users', $roleModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\User', $roleModel);
        $this->assertStringContainsString('public function users()', $roleModel);
        $this->assertStringContainsString('return $this->belongsToMany(User::class);', $roleModel);

        $this->assertStringNotContainsString('roles', $userMigration);
        $this->assertStringNotContainsString('users', $roleMigration);

        $this->assertStringContainsString(
            "\$table->unsignedBigInteger('role_id');\n            \$table->unsignedBigInteger('user_id');",
            $pivotMigration
        );
    }

    public function test_error_when_guessed_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [permissions] relation of [User] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'permissions' => 'belongsToMany'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_issued_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [role] relation of [User] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'role' => 'belongsToMany:Permission'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_allows_pivot_table_migration_override()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany using:Permission'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany using:Permission',
                ],
                'Permission' => [
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExists($this->app->path('RoleUser.php'));
        $this->assertFileNotExists(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));

        $userModel = $this->filesystem->get($this->app->path('User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Role.php'));
        $pivotModel = $this->filesystem->get($this->app->path('Permission.php'));

        $userMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );
        $roleMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_roles_table.php')
        );
        $pivotMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $roles', $userModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Role', $userModel);
        $this->assertStringContainsString('public function roles()', $userModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(Role::class)->using(Permission::class);', $userModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users', $roleModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\User', $roleModel);
        $this->assertStringContainsString('public function users()', $roleModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(User::class)->using(Permission::class);', $roleModel);

        $this->assertStringContainsString('class Permission extends Pivot', $pivotModel);
        $this->assertStringContainsString('@property-read \App\User $user', $pivotModel);
        $this->assertStringContainsString('@property-read \App\Role $role', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Role', $pivotModel);
        $this->assertStringContainsString('public function user()', $pivotModel);
        $this->assertStringContainsString('public function role()', $pivotModel);
        $this->assertStringContainsString('return $this->belongsTo(User::class);', $pivotModel);
        $this->assertStringContainsString('return $this->belongsTo(Role::class);', $pivotModel);

        $this->assertStringNotContainsString('$table->id();', $pivotMigration);
        $this->assertStringContainsString(
            "\$table->unsignedBigInteger('user_id'); // Created for [user] relation.", $pivotMigration
        );
        $this->assertStringContainsString(
            "\$table->unsignedBigInteger('role_id'); // Created for [role] relation.\n", $pivotMigration
        );

        $this->assertStringNotContainsString('roles', $userMigration);
        $this->assertStringNotContainsString('users', $roleMigration);
    }

    public function test_does_not_accepts_with_default()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [roles] relation type [belongsToMany] in [User] doesn't accepts [withDefault].");

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany withDefault'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_accepts_with_pivot()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany withPivot:foo,bar'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany withPivot:foo,bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $userModel = $this->filesystem->get($this->app->path('User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Role.php'));

        $this->assertStringContainsString(
            "return \$this->belongsToMany(Role::class)->withPivot('foo', 'bar');", $userModel);
        $this->assertStringContainsString(
            "return \$this->belongsToMany(User::class)->withPivot('foo', 'bar');", $roleModel);
    }

    public function test_creates_pivot_table_migration_if_one_relation_does_not_uses_using()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany using:Permission'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
                'Permission' => [
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));
        $this->assertFileExists($this->app->path('Permission.php'));
        $this->assertFileExists(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php'));
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
