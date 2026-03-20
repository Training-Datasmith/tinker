<?php

declare (strict_types=1);
namespace Laravel\Tinker;

use Illuminate\Support\Str;
use Psy\Shell;
class Class_Alias_Autoloader
{
    /**
     * The shell instance.
     *
     * @var \Psy\Shell
     */
    protected $shell;
    /**
     * All of the discovered classes.
     *
     * @var array
     */
    protected $classes = [];
    /**
     * Path to the vendor directory.
     *
     * @var string
     */
    protected $vendor_path;
    /**
     * Explicitly included namespaces/classes.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $included_aliases;
    /**
     * Excluded namespaces/classes.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $excluded_aliases;
    /**
     * Register a new alias loader instance.
     *
     * @param  string  $classMapPath
     * @return static
     */
    public static function register(Shell $shell, $class_map_path, array $included_aliases = [], array $excluded_aliases = [])
    {
        return tap(new static($shell, $class_map_path, $included_aliases, $excluded_aliases), function ($loader): void {
            spl_autoload_register([$loader, 'aliasClass']);
        });
    }
    /**
     * Create a new alias loader instance.
     *
     * @param  string  $classMapPath
     */
    public function __construct(Shell $shell, $class_map_path, array $included_aliases = [], array $excluded_aliases = [])
    {
        $this->shell = $shell;
        $this->vendor_path = dirname($class_map_path, 2);
        $this->included_aliases = collect($included_aliases);
        $this->excluded_aliases = collect($excluded_aliases);
        $classes = require $class_map_path;
        foreach ($classes as $class => $path) {
            if (!$this->is_aliasable($class, $path)) {
                continue;
            }
            $name = class_basename($class);
            if (!isset($this->classes[$name])) {
                $this->classes[$name] = $class;
            }
        }
    }
    /**
     * Find the closest class by name.
     *
     * @param  string  $class
     */
    public function alias_class($class): void
    {
        if (Str::contains($class, '\\')) {
            return;
        }
        $full_name = $this->classes[$class] ?? false;
        if ($full_name) {
            $this->shell->write_stdout("[!] Aliasing '{$class}' to '{$full_name}' for this Tinker session.\n");
            class_alias($full_name, $class);
        }
    }
    /**
     * Unregister the alias loader instance.
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'aliasClass']);
    }
    /**
     * Handle the destruction of the instance.
     */
    public function __destruct()
    {
        $this->unregister();
    }
    /**
     * Whether a class may be aliased.
     *
     * @param  string  $class
     * @param  string  $path
     */
    public function is_aliasable($class, $path): bool
    {
        if (!Str::contains($class, '\\')) {
            return false;
        }
        if ($this->included_aliases->contains(function ($alias) use ($class) {
            return Str::starts_with($class, $alias);
        })) {
            return true;
        }
        if (Str::starts_with($path, $this->vendor_path)) {
            return false;
        }
        if ($this->excluded_aliases->contains(function ($alias) use ($class) {
            return Str::starts_with($class, $alias);
        })) {
            return false;
        }
        return true;
    }
}