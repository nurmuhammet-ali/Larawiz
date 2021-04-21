<?php

namespace Larawiz\Larawiz\Parsers\Database;

use Illuminate\Pipeline\Pipeline;

class DatabaseParserPipeline extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\PrepareModels::class,                             // Creates a "Model" class for each model.
        Pipes\ParseQuickTraits::class,                          // Adds the traits to the model.
        Pipes\ParseQuickModelData::class,                       // Complete the information from a Quick Model.
        Pipes\ParseModelTableName::class,                       // Gets the table name for each model.
        Pipes\ParseModelType::class,                            // Set the type of Model (User, pivot, etc)
        Pipes\ParseModelColumns::class,
        Pipes\ParseModelPrimaryKey::class,
        Pipes\ParseModelSoftDeletesColumns::class,
        Pipes\ParseModelTimestampsColumns::class,
        Pipes\ParseModelPerPage::class,                         // Set the model default pagination.
        Pipes\ParseReservationOfRelations::class,               // Reserves relations in the relations array.

        Pipes\ParsePreliminaryBelongsToData::class,             // Parses "belongsTo" relations.
        Pipes\ParsePreliminaryMorphToData::class,               // Parses "morphTo" relations.

        Pipes\ParsePreliminaryHasOneOrManyData::class,          // Parses "hasOne" and "hasMany" relations.
        Pipes\ParsePreliminaryHasOneOrManyThroughData::class,   // Parses "hasOneThrough" and "hasManyThrough" relations.
        Pipes\ParsePreliminaryMorphOneOrManyData::class,        // Parses "morphOne" and "morphMany" relations.
        Pipes\ParsePreliminaryBelongsToManyData::class,           // Parses "belongsToMany" relations.
        Pipes\ParsePreliminaryMorphToManyOrMorphedByMany::class,  // Parses "morphToMany" and "morphedByMany" relations.

        Pipes\ParseModelBelongToColumnRelation::class,          // Creates BelongsTo columns of the relations.
        Pipes\ParseModelMorphToColumnRelation::class,           // Creates MorphTo columns of the relations.

        Pipes\ParseValidateHasOneOrManyThroughRelations::class, // Validates if through relations has needed columns.

        Pipes\ParseBelongsToManyAutomaticPivot::class,          // Creates a auto-pivot for "belongsToMany" relations
        Pipes\ParseMorphToOrByManyAutomaticPivot::class,        // Creates a auto-pivot for "belongsToMany" relations

        Pipes\ParsePivotModelsMigrations::class,                // Cleans Pivot models migrations.

        Pipes\ParseMigrationFromModel::class,                   // Creates a migration for each model.
        Pipes\ParseModelIndexesForMigration::class,             // Adds additional indexes declared for the model.
        Pipes\ParseModelFillable::class,                        // Set each model fillable properties.
        Pipes\ParseModelHidden::class,                          // Adds hidden columns to the list.
        Pipes\ParseModelLocalScopes::class,                     // Sets the model Local Scopes functions.
        Pipes\ParseModelObserver::class,                        // Set model eloquent events.
        Pipes\ParseModelRouteBinding::class,                    // Set model column to use as route binding.
        Pipes\ParseModelFactory::class,                         // Set the factory states.
        Pipes\ParseMigrations::class,                           // Parse all the migrations from the raw data.
        Pipes\ParseGlobalScopes::class,                         // Add the Global Scopes to the model.
        Pipes\ParseModelSeeders::class,                         // Enable or disable seeder creation.
    ];
}
