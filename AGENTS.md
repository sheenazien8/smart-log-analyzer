# Agent Guidelines for Smart Log Analyzer Laravel Package

## Build/Test Commands
- `composer install` - Install dependencies
- `composer test` or `vendor/bin/phpunit` - Run all tests
- `vendor/bin/phpunit --filter TestName` - Run single test
- `composer lint` or `vendor/bin/phpcs` - Code style check
- `composer fix` or `vendor/bin/phpcbf` - Auto-fix code style

## Code Style Guidelines
- Follow PSR-12 coding standards and Laravel conventions
- Use StudlyCaps for class names, camelCase for methods/properties
- Namespace: `SmartLogAnalyzer\` as root namespace
- Models in `Models/`, Services in `Services/`, Jobs in `Jobs/`
- Use type hints for all method parameters and return types
- Prefer dependency injection over facades in services
- Use Laravel's validation, queues, and cache systems
- Add docblocks for all public methods and complex logic

## Laravel Package Structure
- Service Provider: `SmartLogAnalyzerServiceProvider.php`
- Config: `config/smart-log-analyzer.php`
- Migrations: `database/migrations/`
- Views: `resources/views/` with `.blade.php` extension
- Routes: `routes/web.php` for dashboard routes
- Commands: Artisan commands in `Commands/` directory