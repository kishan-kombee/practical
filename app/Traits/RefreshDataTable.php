<?php

namespace App\Traits;

trait RefreshDataTable
{
    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refreshTable' => '$refresh',
            ]
        );
    }
}
