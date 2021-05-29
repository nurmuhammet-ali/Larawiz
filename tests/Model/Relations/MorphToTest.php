<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class MorphToTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_morph_one_guesses_model_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Classroom.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\Student|\App\Models\Teacher $assistable', $model);
        static::assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphTo|\App\Models\Student|\App\Models\Teacher', $model);
        static::assertStringContainsString('public function assistable()', $model);
        static::assertStringContainsString('return $this->morphTo();', $model);

        static::assertStringContainsString("\$table->morphs('assistable');", $migration);
    }

    public function test_morph_one_creates_column_using_source_model_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );

        static::assertStringContainsString("\$table->morphs('assistable');", $migration);
    }

    public function test_creates_column_using_parent_models_as_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'uuid' => null,
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'uuid' => null,
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );
        static::assertStringContainsString("\$table->uuidMorphs('assistable');", $migration);
    }

    public function test_error_when_parent_columns_use_both_id_and_uuid()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Models pointing to [assistable] in [Classroom] must ALL use [uuid] or [id].');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'uuid' => null,
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_morphs_using_column_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo:foobar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Classroom.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\Student|\App\Models\Teacher $assistable', $model);
        static::assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphTo|\App\Models\Student|\App\Models\Teacher', $model);
        static::assertStringContainsString('public function assistable()', $model);
        static::assertStringContainsString("return \$this->morphTo('foobar');", $model);

        static::assertStringContainsString("\$table->morphs('foobar');", $migration);
    }

    public function test_accepts_with_default()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo:foobar withDefault',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Classroom.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\Student|\App\Models\Teacher $assistable', $model);
        static::assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphTo|\App\Models\Student|\App\Models\Teacher', $model);
        static::assertStringContainsString('public function assistable()', $model);
        static::assertStringContainsString("return \$this->morphTo('foobar')->withDefault();", $model);

        static::assertStringContainsString("\$table->morphs('foobar');", $migration);
    }

    public function test_accepts_nullable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo:foobar nullable',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Classroom.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_classrooms_table.php')
        );

        static::assertStringContainsString('@property-read null|\App\Models\Student|\App\Models\Teacher $assistable', $model);
        static::assertStringContainsString("return \$this->morphTo('foobar')", $model);

        static::assertStringContainsString("\$table->nullableMorphs('foobar');", $migration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
