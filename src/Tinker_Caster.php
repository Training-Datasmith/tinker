<?php

declare (strict_types=1);
namespace Laravel\Tinker;

use Exception;
use Symfony\Component\Var_Dumper\Caster\Caster;
class Tinker_Caster
{
    /**
     * Application methods to include in the presenter.
     *
     * @var array
     */
    private static $app_properties = ['configurationIsCached', 'environment', 'environmentFile', 'isLocal', 'routesAreCached', 'runningUnitTests', 'version', 'path', 'basePath', 'configPath', 'databasePath', 'langPath', 'publicPath', 'storagePath', 'bootstrapPath'];
    /**
     * Get an array representing the properties of an application.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public static function cast_application($app): array
    {
        $results = [];
        foreach (self::$app_properties as $property) {
            try {
                $val = $app->{$property}();
                if (!is_null($val)) {
                    $results[Caster::PREFIX_VIRTUAL . $property] = $val;
                }
            } catch (Exception $e) {
            }
        }
        return $results;
    }
    /**
     * Get an array representing the properties of a collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     */
    public static function cast_collection($collection): array
    {
        return [Caster::PREFIX_VIRTUAL . 'all' => $collection->all()];
    }
    /**
     * Get an array representing the properties of an html string.
     *
     * @param  \Illuminate\Support\HtmlString  $htmlString
     */
    public static function cast_html_string($html_string): array
    {
        return [Caster::PREFIX_VIRTUAL . 'html' => $html_string->to_html()];
    }
    /**
     * Get an array representing the properties of a fluent string.
     *
     * @param  \Illuminate\Support\Stringable  $stringable
     */
    public static function cast_stringable($stringable): array
    {
        return [Caster::PREFIX_VIRTUAL . 'value' => (string) $stringable];
    }
    /**
     * Get an array representing the properties of a process result.
     *
     * @param  \Illuminate\Process\ProcessResult  $result
     */
    public static function cast_process_result($result): array
    {
        return [Caster::PREFIX_VIRTUAL . 'output' => $result->output(), Caster::PREFIX_VIRTUAL . 'errorOutput' => $result->error_output(), Caster::PREFIX_VIRTUAL . 'exitCode' => $result->exit_code(), Caster::PREFIX_VIRTUAL . 'successful' => $result->successful()];
    }
    /**
     * Get an array representing the properties of a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public static function cast_model($model): array
    {
        $attributes = array_merge($model->get_attributes(), $model->get_relations());
        $visible = array_flip($model->get_visible() ?: array_diff(array_keys($attributes), $model->get_hidden()));
        $hidden = array_flip($model->get_hidden());
        $appends = (function (): array {
            return array_combine($this->appends, $this->appends);
            // @phpstan-ignore-line
        })->bind_to($model, $model)();
        foreach ($appends as $appended) {
            $attributes[$appended] = $model->{$appended};
        }
        $results = [];
        foreach ($attributes as $key => $value) {
            $prefix = '';
            if (isset($visible[$key])) {
                $prefix = Caster::PREFIX_VIRTUAL;
            }
            if (isset($hidden[$key])) {
                $prefix = Caster::PREFIX_PROTECTED;
            }
            $results[$prefix . $key] = $value;
        }
        return $results;
    }
}