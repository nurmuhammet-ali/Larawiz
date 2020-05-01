<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class MorphOneTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_guesses_target_model_with_unique_morph_to()
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

        $studentModel = $this->filesystem->get($this->app->path('Student.php'));
        $teacherModel = $this->filesystem->get($this->app->path('Teacher.php'));
        $studentMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_students_table.php')
        );
        $teacherMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_teachers_table.php')
        );

        $this->assertStringContainsString('@property-read \App\Classroom $classroom', $studentModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $studentModel);
        $this->assertStringContainsString('public function classroom()', $studentModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $studentModel);

        $this->assertStringContainsString('@property-read \App\Classroom $classroom', $teacherModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $teacherModel);
        $this->assertStringContainsString('public function classroom()', $teacherModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $teacherModel);

        $this->assertStringNotContainsString("'assistable'", $studentMigration);
        $this->assertStringNotContainsString("'assistable'", $teacherMigration);
    }

    public function test_issued_target_model_with_unique_morph_to_and_different_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Classroom',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $studentModel = $this->filesystem->get($this->app->path('Student.php'));
        $teacherModel = $this->filesystem->get($this->app->path('Teacher.php'));
        $studentMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_students_table.php')
        );
        $teacherMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_teachers_table.php')
        );

        $this->assertStringContainsString('@property-read \App\Classroom $foo', $studentModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $studentModel);
        $this->assertStringContainsString('public function foo()', $studentModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $studentModel);

        $this->assertStringContainsString('@property-read \App\Classroom $bar', $teacherModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $teacherModel);
        $this->assertStringContainsString('public function bar()', $teacherModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $teacherModel);

        $this->assertStringNotContainsString("'assistable'", $studentMigration);
        $this->assertStringNotContainsString("'assistable'", $teacherMigration);
    }

    public function test_error_when_guessed_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [bar] relation of [Teacher] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_issued_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [bar] relation of [Teacher] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Class',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_issued_model_with_relation_key_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [bar] relation of [Teacher] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom,assistable',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Class,doesnt_exists',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_parent_model_has_no_id_or_uuid_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Models pointing to [assistable] in [Classroom] must ALL use [uuid] or [id].');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'columns' => [
                        'classroom' => 'morphOne',
                    ],
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_guess_and_target_relation_has_no_morph_to_relation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [Classroom] doesn't have a [morphTo] relation.");

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne',
                ],
                'Teacher'   => [
                    'classroom' => 'morphOne',
                ],
                'Classroom' => [
                    'assistable' => 'belongsTo:Student',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_with_morph_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom,assistable',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Classroom,assistable',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $studentModel = $this->filesystem->get($this->app->path('Student.php'));
        $teacherModel = $this->filesystem->get($this->app->path('Teacher.php'));
        $studentMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_students_table.php')
        );
        $teacherMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_teachers_table.php')
        );

        $this->assertStringContainsString('@property-read \App\Classroom $foo', $studentModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $studentModel);
        $this->assertStringContainsString('public function foo()', $studentModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $studentModel);

        $this->assertStringContainsString('@property-read \App\Classroom $bar', $teacherModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $teacherModel);
        $this->assertStringContainsString('public function bar()', $teacherModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $teacherModel);

        $this->assertStringNotContainsString("'assistable'", $studentMigration);
        $this->assertStringNotContainsString("'assistable'", $teacherMigration);
    }

    public function test_error_when_morph_name_relation_does_not_exists_in_target_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [foo] points to a polymorphic relation [foo] that doesn't exists in [Classroom].");

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom,foo',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Classroom,bar',
                ],
                'Classroom' => [
                    'assistable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_guessing_relation_from_target_model_has_many_morph_to()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Classroom] has multiple [morphTo] relations, you need to pick one.');

        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'foo' => 'morphOne:Classroom',
                ],
                'Teacher'   => [
                    'bar' => 'morphOne:Classroom',
                ],
                'Classroom' => [
                    'assistable'  => 'morphTo',
                    'conversable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_accepts_with_default()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Student'   => [
                    'classroom' => 'morphOne withDefault',
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

        $studentModel = $this->filesystem->get($this->app->path('Student.php'));
        $teacherModel = $this->filesystem->get($this->app->path('Teacher.php'));
        $studentMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_students_table.php')
        );
        $teacherMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_teachers_table.php')
        );

        $this->assertStringContainsString('@property-read \App\Classroom $classroom', $studentModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $studentModel);
        $this->assertStringContainsString('public function classroom()', $studentModel);
        $this->assertStringContainsString(
            "return \$this->morphOne(Classroom::class, 'assistable')->withDefault();", $studentModel);

        $this->assertStringContainsString('@property-read \App\Classroom $classroom', $teacherModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphOne|\App\Classroom', $teacherModel);
        $this->assertStringContainsString('public function classroom()', $teacherModel);
        $this->assertStringContainsString("return \$this->morphOne(Classroom::class, 'assistable');", $teacherModel);

        $this->assertStringNotContainsString("'assistable'", $studentMigration);
        $this->assertStringNotContainsString("'assistable'", $teacherMigration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
