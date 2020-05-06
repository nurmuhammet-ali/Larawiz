<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class SeedersTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_automatically_creates_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_custom_model_automatically_creates_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_disables_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'seeder' => false,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_replaces_model_strings_in_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->shouldMockSeederFile(false);

        $this->artisan('larawiz:scaffold');

        $content = $this->filesystem->get($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));

        $this->assertEquals(<<<'CONTENT'
<?php

use LogicException;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factory;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param  \Illuminate\Database\Eloquent\Factory  $factory
     * @param  \App\User  $user
     * @return void
     */
    public function run(Factory $factory, User $user)
    {
        if ($this->modelHasFactory($factory)) {
            $this->createRecords($factory, $this->amount($user));

            $this->createAdditionalRecords($factory);

            return $this->createStates($factory);
        }

        throw new LogicException('The [User] has no factory defined to use for seeding.');
    }

    /**
     * Creates additional records to populate the database.
     *
     * @param  \Illuminate\Database\Eloquent\Factory  $factory
     */
    protected function additionalRecords($factory)
    {
        // This method is a convenient way to add personalized records.
        //
        // $factory->of(User::class)->create(['name' => 'John Doe']);
    }

    /**
     * Creates additional record by using states.
     *
     * @param  \Illuminate\Database\Eloquent\Factory  $factory
     * @return void
     */
    public function createStates($factory)
    {
        // If your User model has states defined, you can add them here too.
        //
        // $factory->of(User::class)->times(10)->state('state')->create();
    }

    /**
     * Returns a useful amount of records to create.
     *
     * @param \App\User $user
     * @return int
     */
    protected function amount($user)
    {
        // We will conveniently create to two and a half pages of User.
        return (int) ($user->getPerPage() * 2.5);
    }

    /**
     * Check if the model has a factory defined.
     *
     * @param  \Illuminate\Database\Eloquent\Factory  $factory
     * @return bool
     */
    protected function modelHasFactory($factory)
    {
        return isset($factory[User::class]);
    }

    /**
     * Populate the model records using the factory definition.
     *
     * @param  \Illuminate\Database\Eloquent\Factory  $factory
     * @param  int  $amount
     */
    protected function createRecords($factory, int $amount)
    {
        $factory->of(User::class)->times($amount)->create();
    }
}

CONTENT
        ,$content);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
