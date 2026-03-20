# Architecture: tinker

## Purpose

Laravel Tinker — an interactive REPL (Read-Eval-Print Loop) for Laravel applications built on PsySH. Allows developers to interact with the full application context (models, services, facades) from the command line.

## Directory Structure

```
src/
  Tinker_Service_Provider.php   - Laravel service provider; registers the tinker command and casters
  Console/
    Tinker_Command.php          - Artisan command (php artisan tinker); bootstraps and runs PsySH
  Class_Alias_Autoloader.php    - Autoloader that resolves short class names to fully-qualified names
  Tinker_Caster.php             - PsySH caster: pretty-prints Eloquent models and collections
```

## Key Design Decisions

- **PsySH delegate**: Tinker does not implement a REPL itself — it configures and delegates to PsySH (`psy/psysh`), providing Laravel-specific configuration (include paths, casters, autoloading).
- **Alias autoloader**: `Class_Alias_Autoloader` intercepts attempts to load short class names (e.g., `User`) and maps them to their fully-qualified equivalents (e.g., `App\Models\User`), matching the class aliases in `config/app.php`.
- **Tinker caster**: Registers a PsySH caster for Eloquent `Model` and `Collection` objects, displaying them in a human-readable format in the REPL output.
- **Allowed classes config**: `tinker.allowed_with_symlinks` config key lets teams whitelist classes that can be initialised even when running under a symlinked deployment.

## Extension Points

- Add custom PsySH casters by extending `Tinker_Caster` and registering them in the service provider.
- Add pre-load scripts via the `tinker.include` config key to automatically require files at REPL startup.

## Dependency Flow

```
php artisan tinker
  └─> Tinker_Command::handle()
        └─> Class_Alias_Autoloader::register() — enables short-name resolution
        └─> PsySH\Shell::run()                 — interactive REPL
              └─> Tinker_Caster               — pretty-prints Eloquent objects
```
