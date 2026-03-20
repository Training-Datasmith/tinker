<?php

declare (strict_types=1);
namespace Laravel\Tinker\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Laravel\Tinker\Class_Alias_Autoloader;
use Psy\Configuration;
use Psy\Shell;
use Psy\Version_Updater\Checker;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Option;
class Tinker_Command extends Command
{
    /**
     * Artisan commands to include in the tinker shell.
     *
     * @var array
     */
    protected $command_whitelist = ['clear-compiled', 'down', 'env', 'inspire', 'migrate', 'migrate:install', 'optimize', 'up'];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tinker';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interact with your application';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->get_application()->set_catch_exceptions(false);
        $config = Configuration::from_input($this->input);
        $config->set_update_check(Checker::NEVER);
        $app_config = $this->get_laravel()->make('config');
        $config->set_trust_project($app_config->get('tinker.trust_project'));
        $config->get_presenter()->add_casters($this->get_casters());
        if ($this->option('execute')) {
            $config->set_raw_output(true);
        }
        $shell = new Shell($config);
        $shell->add_commands($this->get_commands());
        $shell->set_includes($this->argument('include'));
        $path = Env::get('COMPOSER_VENDOR_DIR', $this->get_laravel()->base_path() . DIRECTORY_SEPARATOR . 'vendor');
        $path .= '/composer/autoload_classmap.php';
        $loader = Class_Alias_Autoloader::register($shell, $path, $app_config->get('tinker.alias', []), $app_config->get('tinker.dont_alias', []));
        if ($code = $this->option('execute')) {
            try {
                $shell->set_output($this->output);
                $shell->execute($code);
            } finally {
                $loader->unregister();
            }
            return 0;
        }
        try {
            return $shell->run();
        } finally {
            $loader->unregister();
        }
    }
    /**
     * Get artisan commands to pass through to PsySH.
     *
     * @return array
     */
    protected function get_commands()
    {
        $commands = [];
        foreach ($this->get_application()->all() as $name => $command) {
            if (in_array($name, $this->command_whitelist)) {
                $commands[] = $command;
            }
        }
        $config = $this->get_laravel()->make('config');
        foreach ($config->get('tinker.commands', []) as $command) {
            $commands[] = $this->get_application()->add($this->get_laravel()->make($command));
        }
        return $commands;
    }
    /**
     * Get an array of Laravel tailored casters.
     *
     * @return array
     */
    protected function get_casters()
    {
        $casters = ['Illuminate\Support\Collection' => 'Laravel\Tinker\TinkerCaster::castCollection', 'Illuminate\Support\HtmlString' => 'Laravel\Tinker\TinkerCaster::castHtmlString', 'Illuminate\Support\Stringable' => 'Laravel\Tinker\TinkerCaster::castStringable'];
        if (class_exists('Illuminate\Database\Eloquent\Model')) {
            $casters['Illuminate\Database\Eloquent\Model'] = 'Laravel\Tinker\TinkerCaster::castModel';
        }
        if (class_exists('Illuminate\Process\ProcessResult')) {
            $casters['Illuminate\Process\ProcessResult'] = 'Laravel\Tinker\TinkerCaster::castProcessResult';
        }
        if (class_exists('Illuminate\Foundation\Application')) {
            $casters['Illuminate\Foundation\Application'] = 'Laravel\Tinker\TinkerCaster::castApplication';
        }
        $config = $this->get_laravel()->make('config');
        return array_merge($casters, (array) $config->get('tinker.casters', []));
    }
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function get_arguments()
    {
        return [['include', Input_Argument::IS_ARRAY, 'Include file(s) before starting tinker']];
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function get_options()
    {
        return [['execute', null, Input_Option::VALUE_OPTIONAL, 'Execute the given code using Tinker']];
    }
}