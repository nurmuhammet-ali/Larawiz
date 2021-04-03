<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class MorphManyTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_guesses_target_model_with_unique_morph_to()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'tags' => 'morphMany',
                ],
                'Video'   => [
                    'tags' => 'morphMany',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $photoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Video.php'));
        $photoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_photos_table.php')
        );
        $videoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_videos_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags', $photoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $photoModel);
        $this->assertStringContainsString('public function tags()', $photoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $photoModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags', $videoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $videoModel);
        $this->assertStringContainsString('public function tags()', $videoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $videoModel);

        $this->assertStringNotContainsString("'taggable'", $photoMigration);
        $this->assertStringNotContainsString("'taggable'", $videoMigration);
    }

    public function test_issued_target_model_with_unique_morph_to_and_different_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'foo' => 'morphMany:Tag',
                ],
                'Video'   => [
                    'bar' => 'morphMany:Tag',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $photoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Video.php'));
        $photoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_photos_table.php')
        );
        $videoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_videos_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $foo', $photoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $photoModel);
        $this->assertStringContainsString('public function foo()', $photoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $photoModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $bar', $videoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $videoModel);
        $this->assertStringContainsString('public function bar()', $videoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $videoModel);

        $this->assertStringNotContainsString("'taggable'", $photoMigration);
        $this->assertStringNotContainsString("'taggable'", $videoMigration);
    }

    public function test_error_when_guessed_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [bar] relation of [Video] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'tags' => 'morphMany',
                ],
                'Video'   => [
                    'bar' => 'morphMany',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_issued_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [bar] relation of [Video] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'foo' => 'morphMany:Tag',
                ],
                'Video'   => [
                    'bar' => 'morphMany:Category',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_parent_model_has_no_id_or_uuid_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Models pointing to [taggable] in [Tag] must ALL use [uuid] or [id].');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'tags' => 'morphMany',
                ],
                'Video'   => [
                    'columns' => [
                        'tags' => 'morphMany',
                    ]
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_guess_and_target_relation_has_no_morph_to_relation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [Tag] doesn't have a [morphTo] relation.");

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'tags' => 'morphMany',
                ],
                'Video'   => [
                    'tags' => 'morphMany',
                ],
                'Tag' => [
                    'taggable' => 'belongsTo:Photo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_with_morph_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'foo' => 'morphMany:Tag,taggable',
                ],
                'Video'   => [
                    'bar' => 'morphMany:Tag,taggable',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $photoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Models' . DS . 'Video.php'));
        $photoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_photos_table.php')
        );
        $videoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_videos_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $foo', $photoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $photoModel);
        $this->assertStringContainsString('public function foo()', $photoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $photoModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $bar', $videoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Models\Tag', $videoModel);
        $this->assertStringContainsString('public function bar()', $videoModel);
        $this->assertStringContainsString("return \$this->morphMany(Tag::class, 'taggable');", $videoModel);

        $this->assertStringNotContainsString("'taggable'", $photoMigration);
        $this->assertStringNotContainsString("'taggable'", $videoMigration);
    }

    public function test_error_when_morph_name_relation_does_not_exists_in_target_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [foo] points to a polymorphic relation [foo] that doesn't exists in [Tag].");

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'foo' => 'morphMany:Tag,foo',
                ],
                'Video'   => [
                    'bar' => 'morphMany:Tag,bar',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_guessing_relation_from_target_model_has_many_morph_to()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Tag] has multiple [morphTo] relations, you need to pick one.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'foo' => 'morphMany:Tag',
                ],
                'Video'   => [
                    'bar' => 'morphMany:Tag',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                    'conversable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_doesnt_accepts_with_default()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [tags] relation type [morphMany] in [Photo] doesn't accepts [withDefault].");

        $this->mockDatabaseFile([
            'models' => [
                'Photo'   => [
                    'tags' => 'morphMany withDefault',
                ],
                'Video'   => [
                    'tags' => 'morphMany',
                ],
                'Tag' => [
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
