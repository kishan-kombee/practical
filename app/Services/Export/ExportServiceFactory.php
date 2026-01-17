<?php

namespace App\Services\Export;

class ExportServiceFactory
{
    /**
     * Available export services
     */
    private static array $services = [
        'role' => RoleExportService::class,
        'user' => UserExportService::class,
        'category' => CategoryExportService::class,
        'subcategory' => SubCategoryExportService::class,
        'product' => ProductExportService::class,
        'appointment' => AppointmentExportService::class,
        'smstemplate' => SmsTemplateExportService::class,
    ];

    /**
     * Create export service instance
     *
     * @throws \InvalidArgumentException
     */
    public static function create(string $exportType): ExportServiceInterface
    {
        $exportType = strtolower($exportType);

        if (! isset(self::$services[$exportType])) {
            throw new \InvalidArgumentException("Export service for type '{$exportType}' not found.");
        }

        $serviceClass = self::$services[$exportType];

        if (! class_exists($serviceClass)) {
            throw new \InvalidArgumentException("Export service class '{$serviceClass}' does not exist.");
        }

        return new $serviceClass();
    }

    /**
     * Register a new export service
     */
    public static function register(string $exportType, string $serviceClass): void
    {
        self::$services[strtolower($exportType)] = $serviceClass;
    }

    /**
     * Get all available export types
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(self::$services);
    }
}
