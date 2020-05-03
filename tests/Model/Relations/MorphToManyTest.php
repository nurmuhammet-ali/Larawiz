<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class MorphToManyTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_error_when_model_not_issued()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [tags] of [Photo] needs an existing polymorphic target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany',
                ],
                'Tag'   => [
                    'name'   => 'string',
                    'photos' => 'morphedByMany',
                    'tags'   => 'morphedByMany',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [tags] of [Photo] needs an existing polymorphic target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Foo',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag',
                ],
                'Tag'   => [
                    'name'   => 'string',
                    'photos' => 'morphedByMany:Photo',
                    'videos' => 'morphedByMany:Video',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_polymorphic_name_not_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [tags] of [Photo] needs an [~ble] relation key.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag',
                ],
                'Tag'   => [
                    'name'   => 'string',
                    'photos' => 'morphedByMany:Photo',
                    'videos' => 'morphedByMany:Video',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_pivot_migration_automatically_using_second_parameter()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Tag'   => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Taggable.php'));

        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php'));

        $photoModel = $this->filesystem->get($this->app->path('Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Video.php'));

        $photoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_photos_table.php')
        );
        $videoMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_videos_table.php')
        );
        $taggableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Tag[] $tags', $photoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphToMany|\App\Tag', $photoModel);
        $this->assertStringContainsString('public function tags()', $photoModel);
        $this->assertStringContainsString("return \$this->morphToMany(Tag::class, 'taggable');", $photoModel);

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Tag[] $tags', $videoModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphToMany|\App\Tag', $videoModel);
        $this->assertStringContainsString('public function tags()', $videoModel);
        $this->assertStringContainsString("return \$this->morphToMany(Tag::class, 'taggable');", $videoModel);

        $this->assertStringNotContainsString('tag', $photoMigration);
        $this->assertStringNotContainsString('tag', $videoMigration);

        $this->assertStringContainsString("\$table->unsignedBigInteger('tag_id');", $taggableMigration);
        $this->assertStringContainsString("\$table->morphs('taggable');", $taggableMigration);
    }

    public function test_creates_pivot_migration_using_parent_models_with_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'uuid' => null,
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Video' => [
                    'uuid' => null,
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Tag'   => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $taggableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php')
        );

        $this->assertStringContainsString("\$table->unsignedBigInteger('tag_id');", $taggableMigration);
        $this->assertStringContainsString("\$table->uuidMorphs('taggable');", $taggableMigration);
    }

    public function test_creates_pivot_migration_using_child_model_using_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Tag'   => [
                    'uuid' => null,
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $taggableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php')
        );

        $this->assertStringContainsString("\$table->uuid('tag_uuid');", $taggableMigration);
        $this->assertStringContainsString("\$table->morphs('taggable');", $taggableMigration);
    }

    public function test_error_when_parents_models_have_not_primary_uniformity()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The polymorphic relation [tags] must have all parent models with same primary key type.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'uuid' => null,
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Tag'   => [
                    'uuid' => null,
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_accepts_with_pivot()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable withPivot:foo,bar',
                ],
                'Video' => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable withPivot:quz',
                ],
                'Tag'   => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $photoModel = $this->filesystem->get($this->app->path('Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Video.php'));

        $this->assertStringContainsString(
            "return \$this->morphToMany(Tag::class, 'taggable')->withPivot('foo', 'bar');", $photoModel);
        $this->assertStringContainsString(
            "return \$this->morphToMany(Tag::class, 'taggable')->withPivot('quz');", $videoModel);
    }

    public function test_overrides_with_pivot_model_and_changes_model_type_to_morph_pivot()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Taggable.php'));

        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php'));

        $photoModel = $this->filesystem->get($this->app->path('Photo.php'));
        $videoModel = $this->filesystem->get($this->app->path('Video.php'));
        $vegetableModel = $this->filesystem->get($this->app->path('Vegetable.php'));

        $vegetableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php')
        );

        $this->assertStringContainsString(
            "return \$this->morphToMany(Tag::class, 'taggable')->using(Vegetable::class);", $photoModel);
        $this->assertStringContainsString(
            "return \$this->morphToMany(Tag::class, 'taggable')->using(Vegetable::class);", $videoModel);

        $this->assertStringContainsString('class Vegetable extends MorphPivot', $vegetableModel);

        $this->assertStringContainsString("\$table->unsignedBigInteger('tag_id');", $vegetableMigration);
        $this->assertStringContainsString("\$table->morphs('taggable');", $vegetableMigration);
        $this->assertStringNotContainsString('$table->id();', $vegetableMigration);
    }

    public function test_pivot_accepts_morph_to_nullable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'morphTo nullable',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $vegetableModel = $this->filesystem->get($this->app->path('Vegetable.php'));
        $vegetableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Photo|\App\Video $taggable', $vegetableModel);
        $this->assertStringContainsString("\$table->nullableMorphs('taggable');", $vegetableMigration);
    }

    public function test_error_when_pivot_doesnt_uses_morphTo()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The pivot model [Vegetable] must have a [taggable] as [morphTo] relation.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_pivot_model_if_one_parent_doesnt_uses_using()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Taggable.php'));
        $this->assertFileExistsInFilesystem($this->app->path('Vegetable.php'));

        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php'));
        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php'));
    }

    public function test_pivot_model_has_enabled_id_when_manually_is_set()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'id' => null,
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $vegetableModel = $this->filesystem->get($this->app->path('Vegetable.php'));

        $vegetableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php')
        );

        $this->assertStringNotContainsString("protected \$primaryKey = 'id';", $vegetableModel);
        $this->assertStringContainsString('protected $incrementing = true;', $vegetableModel);

        $this->assertStringContainsString('$table->id();', $vegetableMigration);
    }

    public function test_pivot_model_has_custom_primary_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'uuid' => 'thing',
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
                    'taggable' => 'morphTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $vegetableModel = $this->filesystem->get($this->app->path('Vegetable.php'));

        $vegetableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php')
        );

        $this->assertStringContainsString("protected \$primaryKey = 'thing';", $vegetableModel);
        $this->assertStringNotContainsString('protected $incrementing = false;', $vegetableModel);
        $this->assertStringContainsString("protected \$keyType = 'string';", $vegetableModel);

        $this->assertStringContainsString("\$table->uuid('thing');", $vegetableMigration);
    }

    public function test_error_when_using_model_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [tags] relation is using a non-existent [Taggable] model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Vegetable',
                ],
                'Video'         => [
                    'name' => 'string',
                    'tags' => 'morphToMany:Tag,taggable using:Taggable',
                ],
                'Tag'           => [
                    'name' => 'string',
                ],
                'Vegetable' => [
                    'enforce'  => 'bool',
                    'tag'      => 'belongsTo',
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
