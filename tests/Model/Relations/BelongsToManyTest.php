<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

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

        $this->assertFileNotExistsInFilesystem($this->app->path('RoleUser.php'));
        $this->assertFileNotExistsInFilesystem($this->app->path('UserRole.php'));
        $this->assertFileNotExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_user_role_table.php'));

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));
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
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles', $userModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Role', $userModel);
        $this->assertStringContainsString('public function roles()', $userModel);
        $this->assertStringContainsString('return $this->belongsToMany(Role::class);', $userModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users', $roleModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\User', $roleModel);
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

    public function test_error_when_guessing_models_without_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [User] of [role] must have primary keys enabled.');

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'columns' => [
                        'name' => 'string',
                        'role' => 'belongsToMany'
                    ]
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_issued_models_without_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Role] of [foo] must have primary keys enabled.');

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'foo' => 'belongsToMany:Role'
                ],
                'Role' => [
                    'columns' => [
                        'type' => 'string',
                        'bar' => 'belongsToMany:User',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_allows_pivot_model_migration_override()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany using:RoleUser'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany using:RoleUser',
                ],
                'RoleUser' => [
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'RoleUser.php'));
        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));
        $pivotModel = $this->filesystem->get($this->app->path('Models' . DS . 'RoleUser.php'));

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
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles', $userModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Role', $userModel);
        $this->assertStringContainsString('public function roles()', $userModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(Role::class)->using(RoleUser::class);', $userModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users', $roleModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\User', $roleModel);
        $this->assertStringContainsString('public function users()', $roleModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(User::class)->using(RoleUser::class);', $roleModel);

        $this->assertStringContainsString('class RoleUser extends Pivot', $pivotModel);
        $this->assertStringContainsString('@property-read \App\Models\User $user', $pivotModel);
        $this->assertStringContainsString('@property-read \App\Models\Role $role', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Role', $pivotModel);
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

    public function test_allows_pivot_model_migration_override_with_different_name()
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

        $this->assertFileNotExistsInFilesystem($this->app->path('RoleUser.php'));
        $this->assertFileNotExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));
        $pivotModel = $this->filesystem->get($this->app->path('Models' . DS . 'Permission.php'));

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
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles', $userModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Role', $userModel);
        $this->assertStringContainsString('public function roles()', $userModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(Role::class, \'permissions\')->using(Permission::class);', $userModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users', $roleModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\User', $roleModel);
        $this->assertStringContainsString('public function users()', $roleModel);
        $this->assertStringContainsString(
            'return $this->belongsToMany(User::class, \'permissions\')->using(Permission::class);', $roleModel);

        $this->assertStringContainsString('class Permission extends Pivot', $pivotModel);
        $this->assertStringContainsString('@property-read \App\Models\User $user', $pivotModel);
        $this->assertStringContainsString('@property-read \App\Models\Role $role', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $pivotModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Role', $pivotModel);
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

    public function test_issues_table_name_for_pivot_doesnt_get_overwritten()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany:Role,cadabra using:Permission'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany:User,cadabra using:Permission',
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

        $this->assertFileNotExistsInFilesystem($this->app->path('RoleUser.php'));
        $this->assertFileNotExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));
        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php'));

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));

        $this->assertStringContainsString(
            'return $this->belongsToMany(Role::class, \'cadabra\')->using(Permission::class);', $userModel);

        $this->assertStringContainsString(
            'return $this->belongsToMany(User::class, \'cadabra\')->using(Permission::class);', $roleModel);
    }

    public function test_issues_pivot_with_custom_table_name()
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
                    'columns' => [
                        'enforce' => 'bool',
                        'user' => 'belongsTo',
                        'role' => 'belongsTo',
                        'timestamps' => null
                    ],
                    'table' => 'vegetables'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Models' . DS . 'RoleUser.php'));
        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Permission.php'));
        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php'));

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));
        $pivot = $this->filesystem->get($this->app->path('Models' . DS . 'Permission.php'));

        $this->assertStringContainsString(
            'return $this->belongsToMany(Role::class, \'vegetables\')->using(Permission::class);', $userModel);

        $this->assertStringContainsString(
            'return $this->belongsToMany(User::class, \'vegetables\')->using(Permission::class);', $roleModel);

        $this->assertStringContainsString("protected \$table = 'vegetables';", $pivot);
    }

    public function test_error_when_using_pivot_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [users] relation is using a non-existent [Vegetables] model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'   => [
                    'name' => 'string',
                    'roles' => 'belongsToMany using:Permission'
                ],
                'Role' => [
                    'type' => 'string',
                    'users' => 'belongsToMany using:Vegetables',
                ],
                'Permission' => [
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
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

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $roleModel = $this->filesystem->get($this->app->path('Models' . DS . 'Role.php'));

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

        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_role_user_table.php'));
        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Permission.php'));
        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php'));
    }

    public function test_pivot_model_has_enabled_id_when_manually_is_set()
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
                    'id' => null,
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $pivotModel = $this->filesystem->get($this->app->path('Models' . DS . 'Permission.php'));
        $pivotMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php')
        );


        $this->assertStringNotContainsString("protected \$primaryKey = 'id';", $pivotModel);
        $this->assertStringNotContainsString("protected \$keyType = 'int';", $pivotModel);
        $this->assertStringContainsString('public $incrementing = true;', $pivotModel);

        $this->assertStringContainsString('$table->id();', $pivotMigration);
    }

    public function test_pivot_model_has_custom_primary_id()
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
                    'uuid' => 'thing',
                    'enforce' => 'bool',
                    'user' => 'belongsTo',
                    'role' => 'belongsTo',
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $pivotModel = $this->filesystem->get($this->app->path('Models' . DS . 'Permission.php'));
        $pivotMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_permissions_table.php')
        );


        $this->assertStringContainsString("protected \$primaryKey = 'thing';", $pivotModel);
        $this->assertStringContainsString("protected \$keyType = 'string';", $pivotModel);
        $this->assertStringNotContainsString('public $incrementing = false;', $pivotModel);

        $this->assertStringContainsString("\$table->uuid('thing');", $pivotMigration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
