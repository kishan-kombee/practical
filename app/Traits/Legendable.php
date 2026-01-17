<?php

namespace App\Traits;

trait Legendable
{
    /**
     * Common method to populate dynamic values into the template
     *
     * @param $template - user driven template
     * @param $model_array - model name as key and model object as value
     * @param $extra_legend - any extra key value pair to be passed
     * @return mixed
     */
    public static function getDynamicContent($template, $model_array, $extra_legend)
    {
        $legendValueMap = self::mapLegendKeyToModelValues($model_array, $extra_legend);

        return self::populateTemplateWithDynamicValues($legendValueMap, $template);
    }

    public static function mapLegendKeyToModelValues($modelArray, $extraLegendValues)
    {
        $legendValueMap = [];
        if (! is_null($modelArray)) {
            foreach ($modelArray as $tableName => $modelObject) { // key is table name & value in the model object
                $legendArr = config('constants.export_template_legend');
                if (isset($modelObject->legend)) {
                    $legendArr = $modelObject->legend;
                }

                foreach ($legendArr as $legend) {
                    $column_name = self::getColumnNameFromLegend($legend, $tableName);
                    $legendValueMap[$legend] = $modelObject->$column_name;
                }
            }
        }

        return $extraLegendValues == null ? $legendValueMap : array_merge($legendValueMap, $extraLegendValues);
    }

    /**
     * Filter data
     *
     * @return mixed
     */
    public static function populateTemplateWithDynamicValues($legendValueMap, $template)
    {
        if (! empty($legendValueMap)) {
            foreach ($legendValueMap as $legend => $value) {
                // If $value is null, convert it to an empty string
                $value = $value ?? '';
                $template = str_replace($legend, $value, $template);
            }
        }

        return $template;
    }

    /**
     * @return string|string[]
     */
    public static function getColumnNameFromLegend($legend, $tableName)
    {
        $legend = str_replace('{{' . $tableName . '_', '', $legend);

        return str_replace('}}', '', $legend);
    }
}
