# Database Seeding Guide

This guide explains how to seed the database with test data using Laravel factories and seeders.

## Overview

The seeding system uses Laravel Factories and Seeders to create realistic test data following proper relationships and constraints.

## Seeding Order

The seeders run in this specific order (defined in `DatabaseSeeder.php`):

1. **RoleSeeder** - Creates roles (Admin, Manager, User, Guest + 5 random)
2. **PermissionSeeder** - Creates permissions
3. **EmailTemplateSeeder** - Creates email templates
4. **EmailFormatSeeder** - Creates email formats
5. **UserSeeder** - Creates users (1 admin + 10 active + 2 inactive)
6. **LoginHistorySeeder** - Creates login history records
8. **CategorySeeder** - Creates categories (10 active + 2 inactive)
9. **SubCategorySeeder** - Creates sub categories (3-5 per active category)
10. **ProductSeeder** - Creates products (5-10 per sub category + various statuses)
11. **AppointmentSeeder** - Creates appointments
12. **SmsTemplateSeeder** - Creates SMS templates

## Running Seeders

### Fresh Migration with Seeding

```bash
php artisan migrate:fresh --seed
```

### Run Specific Seeder

```bash
php artisan db:seed --class=CategorySeeder
```

### Run All Seeders

```bash
php artisan db:seed
```

## Default Credentials

### Admin User
- **Email:** admin@example.com
- **Password:** password


## Factory Features

### RoleFactory
- Creates unique job titles as role names
- States: `active()`, `inactive()`

### UserFactory
- Creates users with proper relationships to roles
- Mobile numbers: 10-digit starting with 6
- States: `active()`, `inactive()`, `recentlyLoggedIn()`

### CategoryFactory
- Creates realistic category names from predefined list
- States: `active()`, `inactive()`

### SubCategoryFactory
- Creates sub categories linked to categories
- States: `active()`, `inactive()`
- Method: `forCategory($categoryId)`

### ProductFactory
- Creates products with unique item codes (e.g., PRD-1234-AB)
- Properly links to categories and sub categories
- States: `available()`, `notAvailable()`, `lowStock()`, `highStock()`
- Method: `forCategoryAndSubCategory($categoryId, $subCategoryId)`

## Data Relationships

### Categories → Sub Categories → Products

- Each active category gets 3-5 active sub categories
- Each sub category gets 5-10 available products
- Additional products are created with various statuses (not available, low stock, high stock)

### Users → Roles

- Users are linked to roles
- Default admin user is created with Admin role

## Model Relationships

### Category Model
- `subCategories()` - Has many sub categories
- `products()` - Has many products

### SubCategory Model
- `category()` - Belongs to category
- `products()` - Has many products

### Product Model
- `category()` - Belongs to category
- `subCategory()` - Belongs to sub category

## Best Practices

1. **Always run seeders in order** - Relationships depend on previous seeders
2. **Use factories in tests** - Factories are designed for testing
3. **Respect relationships** - Factories automatically handle foreign keys
4. **Use states** - Factory states make it easy to create specific data types

## Example Usage

### Create a single category with sub categories and products

```php
$category = Category::factory()->active()->create();

$subCategory = SubCategory::factory()
    ->forCategory($category->id)
    ->active()
    ->create();

$product = Product::factory()
    ->forCategoryAndSubCategory($category->id, $subCategory->id)
    ->available()
    ->create();
```

### Create multiple products for testing

```php
Product::factory()
    ->count(50)
    ->available()
    ->create();
```

## Notes

- All passwords default to: `password`
- Mobile numbers are 10-digit starting with 6
- Item codes follow pattern: PRD-####-??
- User codes follow pattern: ??#### (e.g., AB1234)
- Most seeders create a mix of active and inactive records for testing
