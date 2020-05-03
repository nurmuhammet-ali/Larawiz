<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class MorphedByManyTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_error_when_no_model_key_is_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [photos] of [Tag] needs an existing polymorphic target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                ],
                'Video' => [
                    'name' => 'string',
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

    public function test_error_when_polymorphic_name_not_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [photos] of [Tag] needs an [~ble] relation key.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                ],
                'Video' => [
                    'name' => 'string',
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

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [videos] of [Tag] needs an existing polymorphic target model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                ],
                'Video' => [
                    'name' => 'string',
                ],
                'Tag'   => [
                    'name'   => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Foo,taggable',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_pivot_automatically_using_second_parameter()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'name' => 'string',
                ],
                'Video' => [
                    'name' => 'string',
                ],
                'Tag'   => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Video,taggable',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Taggable.php'));

        $this->assertFileExistsInFilesystem(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php'));

        $tagModel = $this->filesystem->get($this->app->path('Tag.php'));
        $tagMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_tags_table.php')
        );

        $taggableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_taggables_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Photo[] $photos', $tagModel);
        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Video[] $videos', $tagModel);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\MorphToMany|\App\Photo', $tagModel);
        $this->assertStringContainsString('public function photos()', $tagModel);
        $this->assertStringContainsString("return \$this->morphedByMany(Photo::class, 'taggable');", $tagModel);

        $this->assertStringContainsString("\$table->unsignedBigInteger('tag_id');", $taggableMigration);
        $this->assertStringContainsString("\$table->morphs('taggable');", $taggableMigration);
    }

    public function test_error_when_all_parent_models_have_not_primary_uniformity()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'uuid' => null,
                    'name' => 'string',
                ],
                'Video' => [
                    'name' => 'string',
                ],
                'Tag'   => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Video,taggable',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));
    }

    public function test_creates_pivot_table_using_parents_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo' => [
                    'uuid' => null,
                    'name' => 'string',
                ],
                'Video' => [
                    'uuid' => null,
                    'name' => 'string',
                ],
                'Tag'   => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Video,taggable',
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
                ],
                'Video' => [
                    'name' => 'string',
                ],
                'Tag'   => [
                    'uuid' => null,
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Video,taggable',
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

    public function test_overrides_with_pivot_model_and_changes_model_type_to_morph_pivot()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                ],
                'Video'         => [
                    'name' => 'string',
                ],
                'Tag'           => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable using:Vegetable',
                    'videos' => 'morphedByMany:Video,taggable using:Vegetable',
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

        $tagModel = $this->filesystem->get($this->app->path('Tag.php'));
        $vegetableModel = $this->filesystem->get($this->app->path('Vegetable.php'));

        $vegetableMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_vegetables_table.php')
        );

        $this->assertStringContainsString(
            "return \$this->morphedByMany(Photo::class, 'taggable')->using(Vegetable::class);", $tagModel);
        $this->assertStringContainsString(
            "return \$this->morphedByMany(Video::class, 'taggable')->using(Vegetable::class);", $tagModel);

        $this->assertStringContainsString('class Vegetable extends MorphPivot', $vegetableModel);

        $this->assertStringContainsString("\$table->unsignedBigInteger('tag_id');", $vegetableMigration);
        $this->assertStringContainsString("\$table->morphs('taggable');", $vegetableMigration);
        $this->assertStringNotContainsString('$table->id();', $vegetableMigration);
    }

    public function test_creates_pivot_model_if_one_parent_doesnt_uses_using()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                ],
                'Video'         => [
                    'name' => 'string',
                ],
                'Tag'           => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable',
                    'videos' => 'morphedByMany:Video,taggable using:Vegetable',
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
                ],
                'Video'         => [
                    'name' => 'string',
                ],
                'Tag'           => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable using:Vegetable',
                    'videos' => 'morphedByMany:Video,taggable using:Vegetable',
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
                ],
                'Video'         => [
                    'name' => 'string',
                ],
                'Tag'           => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable using:Vegetable',
                    'videos' => 'morphedByMany:Video,taggable using:Vegetable',
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
        $this->expectExceptionMessage('The [photos] relation is using a non-existent [Taggable] model.');

        $this->mockDatabaseFile([
            'models' => [
                'Photo'         => [
                    'name' => 'string',
                ],
                'Video'         => [
                    'name' => 'string',
                ],
                'Tag'           => [
                    'name' => 'string',
                    'photos' => 'morphedByMany:Photo,taggable using:Taggable',
                    'videos' => 'morphedByMany:Video,taggable using:Taggable',
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
