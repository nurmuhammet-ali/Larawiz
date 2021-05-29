<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelType
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
            if ($type = $scaffold->rawDatabase->get("models.{$key}.type")) {
                $this->setModelType($model, $type);
            }
        }

        return $next($scaffold);
    }

    /**
     * Sets the model type.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $type
     */
    protected function setModelType(Model $model, string $type)
    {
        if (! array_key_exists($type, Model::MODEL_TYPE_MAP)) {
            throw new LogicException("The [{$type}] type for the [{$model->key}] model is not a valid type.");
        }

        $model->modelType = Model::MODEL_TYPE_MAP[$type];
    }
}
