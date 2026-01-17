<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait CreatedbyUpdatedby
{
    public static function bootCreatedbyUpdatedby()
    {
        static::creating(function ($model) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user) {
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
            }
        });

        static::updating(function ($model) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user) {
                $model->updated_by = $user->id;
            }
        });

        static::deleting(function ($model) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user) {
                $model->updated_by = $user->id;
            }
        });
    }

    public static function createLog($user, $model, array $response, int $responseType, bool $fromIndexMethod = false)
    {
        /** @var \App\Models\User|null $user */
        $user = $user;

        $modelName = Str::lower(class_basename($model));

        $whereData = [
            'log_name' => $modelName,
            'description' => $fromIndexMethod ? $response['description'] : ($response['description'] ?? '') . ')',
            'ip_address' => Request::capture()->ip(),
            'subject_type' => 'App\Models\\' . Str::ucfirst($modelName),
            'subject_id' => $fromIndexMethod ? null : ($response['properties']['attributes']['id'] ?? null),
            'causer_type' => 'App\Models\User',
            'causer_id' => $user ? $user->id : null,
            'response_type' => $responseType,
        ];

        $data = $whereData;
        $data['properties'] = json_encode($response['properties'] ?? []);

        if (class_exists(ActivityLog::class)) {
            $activityLog = ActivityLog::where($whereData)
                ->where('created_at', now()->format('Y-m-d H:i:s'))
                ->first();

            if (is_null($activityLog)) {
                ActivityLog::create($data);
            }
        }
    }

    public static function getIndexDescription(array $queryStringArr, string $realURI, string $indexDescription = ''): string
    {
        foreach ($queryStringArr as $queryStr) {
            $queryStrArr = explode('=', $queryStr);

            if (! empty($queryStrArr[1])) {
                if ($indexDescription != '') {
                    $indexDescription .= '|';
                }

                if (Str::contains($queryStr, 'search')) {
                    $indexDescription .= 'Search String: ' . $queryStrArr[1];
                } elseif (Str::contains($queryStr, 'filter')) {
                    $indexDescription .= 'Filter String: ' . $queryStrArr[1];
                } elseif (Str::contains($queryStr, 'sort')) {
                    $indexDescription .= 'Sort field: ' . Str::before($queryStrArr[1], '_id');
                } elseif (Str::contains($queryStr, 'order_by')) {
                    $indexDescription .= 'Order by: ' . $queryStrArr[1];
                } elseif (Str::contains($queryStr, 'page')) {
                    $indexDescription .= 'Page: ' . $queryStrArr[1];
                }
            }
        }

        return $indexDescription;
    }
}
