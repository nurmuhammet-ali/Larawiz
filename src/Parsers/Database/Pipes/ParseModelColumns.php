<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Code\Argument;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;

class ParseModelColumns
{
    /**
     * Handle the parsing of the Database scaffold.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        foreach ($scaffold->database->models as $key => $model) {
            foreach ($scaffold->rawDatabase->get("models.{$key}.columns") as $name => $line) {

                $this->validateIndexing($name, $line);

                // If the columns is a relation declaration, we will check if it needs columns.
                // If it doesn't, we can safely jump over it since relations are later parsed,
                // put if it needs a column for "belongsTo" and "morphs", we will reserve it.
                if ($this->columnIsRelation($line)) {
                    if ($this->relationUsesColumn($line)) {
                        $this->reserveColumnForRelation($model, $name);
                    }
                    continue;
                }
                elseif ($this->columnIsPrimary($name)) {
                    $column = $this->createPrimaryKey($name, $line);
                }
                elseif ($this->columnIsUuid($name)) {
                    $column = $this->createUuidPrimaryKey($name, $line);
                }
                elseif ($this->columnIsShorthand($name)) {
                    $column = $this->createShorthand($name, $line);
                }
                else {
                    $column = $this->createColumn($name, $line);
                }

                $model->columns->put($name, $column);
            }
        }

        return $next($scaffold);
    }

    /**
     * Throw an exception if there is conflicting indexes types.
     *
     * @param  string  $name
     * @param  null|string  $line
     */
    protected function validateIndexing(string $name, ?string $line)
    {
        if ($line && Str::containsAll($line, ['index', 'unique'])) {
            throw new LogicException("The [{$name}] column must contain either [index] or [unique], not both.");
        }
    }

    /**
     * Checks if the Column line is a relation declaration.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function columnIsRelation(?string $line)
    {
        return $line && Str::startsWith($line, array_keys(BaseRelation::RELATION_CLASSES));
    }

    /**
     * Checks if the relations uses a column in the local model.
     *
     * @param  string  $line
     * @return bool
     */
    protected function relationUsesColumn(string $line)
    {
        return in_array((string)Str::of($line)->before(':')->before(' '), BaseRelation::USES_COLUMN, true);
    }

    /**
     * Reserve the column name if needed.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @return void
     */
    protected function reserveColumnForRelation(Model $model, string $name)
    {
        $model->columns->put($name, null);
    }

    /**
     * Checks if the column is a primary column.
     *
     * @param  string  $name
     * @return bool
     */
    protected function columnIsPrimary(string $name)
    {
        return $name === 'id';
    }

    /**
     * Creates a Primary Key column.
     *
     * @param  string  $name  "id: ~", "id: name", "id: ~ something", "id: name something".
     * @param  null|string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function createPrimaryKey(string $name, ?string $line)
    {
        $column = new Column();

        $column->name = $this->firstArgumentOrName($name, $line);
        $column->type = $name;
        $column->methods = Method::parseManyMethods($name . ($line ? ':' . $line : ''));

        return $column;
    }

    /**
     * Check if the column is a potentially UUID primary key.
     *
     * @param  string  $name
     * @return bool
     */
    protected function columnIsUuid(string $name)
    {
        return $name === 'uuid';
    }

    /**
     * Creates an UUID column.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function createUuidPrimaryKey(string $name, ?string $line)
    {
        $column = new Column;

        $column->name = $this->firstArgumentOrName($name, $line);
        $column->type = 'uuid';
        $column->methods = Method::parseManyMethods(Column::normalizeShorthandLine($name, $line));

        // If the primary key is an UUID, we will put the column name as first argument
        // since the UUID method in the Blueprint needs the name of the column. If the
        // declaration did not include the name, the name was already set as default.
        if (! $column->methods->first()->arguments->contains('value', $column->name)) {
            $column->methods->first()->arguments->prepend(new Argument([
                'value' => $column->name,
                'type'  => 'string',
            ]));
        }

        return $column;
    }

    /**
     * Check if the column is a shorthand of something.
     *
     * @param  string  $name
     * @return bool
     */
    protected function columnIsShorthand(string $name)
    {
        return in_array($name, Column::SHORTHANDS, true);
    }

    /**
     * Creates a shorthand column.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function createShorthand(string $name, ?string $line)
    {
        $column = new Column;

        $column->name = Column::getShorthandDefault($name, $line);
        $column->type = $name;
        $column->methods = Method::parseManyMethods(Column::normalizeShorthandLine($name, $line));

        return $column;
    }

    /**
     * Creates a normal column.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function createColumn(string $name, ?string $line)
    {
        $column = new Column();

        $column->name = $name;
        $column->type = $this->firstArgumentOrName($name, $line);
        $column->methods = $this->methodsFromLineOrName($name, $line);

        return $column;
    }

    /**
     * Returns a collection of methods normalized for the column declaration.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[]
     */
    protected function methodsFromLineOrName(string $name, ?string $line)
    {
        return Method::parseManyMethods(Column::normalizeColumnLine($name, $line));
    }

    /**
     * Returns the type of the column based on the line or name itself.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return string
     */
    protected function firstArgumentOrName(string $name, ?string $line)
    {
        if (! $line) {
            return $name;
        }

        $newName = Str::of($line)->before(' ')->before(':')->__toString();

        if (in_array(strtolower($newName), ['~', 'null'])) {
            return $name;
        }

        return $newName;
    }
}
