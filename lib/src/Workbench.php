<?php
namespace FatPanda\WordPress;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Composer\EventDispatcher\Event;
use Illuminate\Support\Composer;
use Symfony\Component\Process\Process;

class Workbench extends Composer {

	public function __construct(Filesystem $files, $workingPath = null)
  {
  	if (is_null($workingPath)) {
  		throw new \Exception("Working path must be specified.");
  	}
    parent::__construct($files, $workingPath);
    $this->bootstrap();
  }

  protected function bootstrap()
	{
		require_once $this->workingPath.'/vendor/autoload.php';
	}

	protected function path($path = null) 
	{
		return rtrim($this->workingPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
	}

	protected function getStub($stub)
	{
		$file = $this->path($stub.'.stub');
		if (!$this->files->exists($file)) {
			throw new \Exception("Can't find stub for '$stub'; looked in $file. Maybe your workbench is corrupted?");
		}
		return $this->files->get($file);
	}

	/**
	 * Setup the Workbench: copy .env.example to create .env, then
	 * fill it with configuration settings prompted from the user,
	 * then regenerate the hashing salts.
	 */
	public function setup()
	{
		$env_file = $this->path('.env');
		$env_example_file = $this->path('.env.example');

		$die = function($error, $code = 1) {
			\cli\out("%1{$error}%n\n");
			\cli\out("To restart Workbench installation: %4composer run-script post-create-project-cmd%n\n");
			exit($code);
		};

		if (!$this->files->exists($env_example_file)) {
			$die("Example environment file {$env_example_file} is missing.", 1);
		}

		if ($this->files->exists($env_file)) {
			$overwrite = \cli\choose("The environment file already exists; do you want to overwrite it", "yn", "n");
			if ("y" !== $overwrite) {
				$die("An environment file, {$env_file}, already exists.", 2);
			}
		}

		$tokens = [];

		\cli\out("%4Let's setup your workbench!%n\n");

		$tokens['database_name'] = \cli\prompt("What is your database name?", "workbench");
		$tokens['database_user'] = \cli\prompt("What username should we use to connect to it?", "root");
		$tokens['database_password'] = \cli\prompt("What password?", "password");
		$tokens['database_host'] = \cli\prompt("What host name should we use for connecting?", "localhost");
		$tokens['http://example.dev'] = \cli\prompt("What should the home URL be for accessing this workbench?", "http://workbench.dev");
		if (!filter_var($tokens['http://example.dev'], FILTER_VALIDATE_URL)) {
			$die("Invalid URL: {$tokens['http://example.dev']}", 4);
		}

		$site_url = parse_url($tokens['http://example.dev']);

		$tokens['WP_ENV=development'] = 'WP_ENV=' . \cli\prompt("What should the default environment be?", "development");

		$stub = $this->files->get($env_example_file);
		$content = str_replace(array_keys($tokens), array_values($tokens), $stub);
		if (!$this->files->put($env_file, $content)) {
			$die("Failed to generate the environment file {$env_file}", 8);
		}

		$this->wp("dotenv salts regenerate");

		\cli\out("%2Success!%n\n");	
	}

	/**
	 * Composer post-create-project-cmd event handler.
	 * Runs Workbench::setup
	 *
	 * @return void
	 */
	static function onPostCreateProject(Event $event = null)
	{
		$workbench = new static(new Filesystem, getcwd());
		$workbench->setup();
	}

	protected function output()
	{
		return function($type, $buffer) {
		  \cli\out($buffer);
		};
	}

	protected function studio($args)
	{
		$process = $this->getProcess();
		$process->setCommandLine("vendor/bin/studio {$args}");
		$process->run($this->output());
		return $process;
	}

	protected function composer($args)
	{
		$process = $this->getProcess();
		$process->setCommandLine(trim($this->findComposer() . " " . $args));
		$process->run($this->output());
		return $process;
	}

	protected function wp($args)
	{
		$process = $this->getProcess();
		$process->setCommandLine("wp {$args} --path=\"{$this->workingPath}\"");
		$process->run($this->output());
		return $process;
	}

	/**
	 * Cleanup a plugin project in the workbench.
	 *
	 * @param String The plugin name
	 * @return void
	 */
	function cleanupPlugin($name)
	{
		$die = function($error, $code = 1) {
			\cli\out("%1{$error}%n\n");
			exit($code);
		};

		$name = Str::slug($name);

		\cli\out("Cleaning up {$name}...\n");

		$composer = $this->path("/workbench/plugins/{$name}/composer.json");
		if (!$this->files->exists($composer)) {
	 		$die("Coudn't find {$composer} for {$name}", 1);
	 	}

	 	$package = json_decode( $this->files->get( $composer ) );
	 	if (false === $package) {
	 		$die("Coudn't read {$composer} for {$name}", 2);
	 	}

	 	// remove from composer
	 	$process = $this->composer("remove {$package->name}");
		if (!$process->isSuccessful()) {
			$die("Failed to remove {$package->name} from workbench", 4);
		}

	 	// remove from studio
	 	$workbench_path = $this->path("/workbench/plugins/{$name}");
	 	$process = $this->studio("unload {$workbench_path}");
	 	if (!$process->isSuccessful()) {
	 		$die("Failed to unload {$package->name} from your workbench", 8);
	 	}

	 	// delete the project
	 	$this->files->deleteDirectory($workbench_path);
	}

	/**
	 * Begin interactive process of generating a new WordPress plugin project.
	 * @param String The plugin folder slug name
	 * @param String The type of plugin: either "plugin" or "mu-plugin"
	 * @return Array of the data collected, including paths to the files generated by the process
	 */
	function createPlugin($name, $type = 'plugin', $args = '')
	{
		$nameSlug = Str::slug($name);
		if ($nameSlug !== $name) {
			$answer = \cli\choose("Is it OK to name your plugin \"{$nameSlug}\" instead", "yn", "y");
			if ('y' !== $answer) {
				\cli\out("%1OK, that's fine. But you have to name your plugin something else.%n\n");
				exit(1);
			}
		}
		$name = $nameSlug;

		$workbench = $this->path("/workbench/{$type}s");

		// make sure we have a propert workbench path setup
		if (!$this->files->exists($workbench)) {
			$this->files->makeDirectory($workbench);
		}

		$output_path = $workbench."/{$name}";
		if (file_exists($output_path)) {
			\cli\out("%1Whoops! There's already a plugin in {$output_path}!%n\n");
			exit(2);
		}

		$this->files->copyDirectory($this->path("/lib/stubs/{$type}"), $output_path);
		$this->files->deleteDirectory($output_path.'/stubs');

		$tokens = [];

		// make sure we have a stub to work with
		try {
			$plugin = static::getStub('/lib/stubs/plugin/stubs/plugin');
			$bootstrap = static::getStub('/lib/stubs/plugin/stubs/bootstrap');
			$composer = static::getStub('/lib/stubs/plugin/stubs/composer');
			$customPostType = static::getStub('/lib/stubs/plugin/stubs/example-custom-post-type');
			$customTaxonomy = static::getStub('/lib/stubs/plugin/stubs/example-custom-taxonomy');
			$autoload = static::getStub('/lib/stubs/plugin/stubs/autoload');
		} catch (\Exception $e) {
			\cli\out("%1".$e->getMessage()."%n\n");
			exit(4);
		}

		$tokens['@@PLUGIN_SLUG@@'] = $name;

		$tokens['@@PLUGIN_NAME@@'] = \cli\prompt("What do you want to call your plugin?", "My Plugin");
		$tokens['@@PLUGIN_DESCRIPTION@@'] = \cli\prompt("What will this plugin do? Be succinct.", "Do something amazing.");

		$tokens['@@PLUGIN_LICENSE@@'] = \cli\prompt("What should the plugin license be?", "GPL2");
		$tokens['@@PLUGIN_LICENSE_URI@@'] = \cli\prompt("At what URL can more information about the license be found?", 
			"https://www.gnu.org/licenses/gpl-2.0.html");

		$tokens['@@PLUGIN_CLASS_NAME@@'] = Str::studly($tokens['@@PLUGIN_NAME@@']);
		$tokens['@@PLUGIN_VAR_NAME@@'] = '_' . Str::slug($name, '_');

		$tokens['@@PLUGIN_VERSION@@'] = \cli\prompt("What should the base version be?", "1.0.0");
		
		$tokens['@@PLUGIN_AUTHOR@@'] = \cli\prompt("Who is the author of this plugin?", get_current_user());
		
		$tokens['@@PLUGIN_AUTHOR_URI@@'] = \cli\prompt("What is the URL of the author's homepage?", 
			"https://github.com/" . Str::slug($tokens['@@PLUGIN_AUTHOR@@']));
		
		$tokens['@@PLUGIN_URI@@'] = \cli\prompt("What is the URL of this project's homepage?", 
			 rtrim($tokens['@@PLUGIN_AUTHOR_URI@@'], '/') . '/' . $name);

		$tokens['@@PLUGIN_NAMESPACE@@'] = \cli\prompt("What should the PHP namespace be for your plugin?", 
				Str::studly($tokens['@@PLUGIN_AUTHOR@@']) . '\\' . Str::studly($name));

		$tokens['@@PLUGIN_TEXT_DOMAIN@@'] = \cli\prompt("What is this plugin's text domain?", Str::slug($tokens['@@PLUGIN_NAME@@']));

		$tokens['@@PLUGIN_DOMAIN_PATH@@'] = \cli\prompt("Where will this plugin's translation files be stored?", "/resources/lang");

		$tokens['@@COMPOSER_NAME@@'] = \cli\prompt("What should the Composer project name be?", 
				Str::slug($tokens['@@PLUGIN_AUTHOR@@']) . '/' . $name);

		$plugin_file = $output_path.'/src/plugin.php';
		$bootstrap_file = $output_path.'/bootstrap.php';
		$composer_file = $output_path.'/composer.json';
		$autoload_file = $output_path.'/vendor/autoload.php';
		
		$this->files->put($bootstrap_file, str_replace(array_keys($tokens), array_values($tokens), $bootstrap));
		$this->files->put($plugin_file, str_replace(array_keys($tokens), array_values($tokens), $plugin));
		$this->files->put($composer_file, str_replace(array_keys($tokens), array_values($tokens), $composer));
		$this->files->put($autoload_file, str_replace(array_keys($tokens), array_values($tokens), $autoload));
		
		$examples = \cli\choose("Should we generate sample custom post types and taxonomies", "yn", "y");

		$result = [
			'tokens' => $tokens,
			'files' => [ 
				'bootstrap' => $bootstrap_file, 
				'plugin' => $plugin_file,
				'composer' => $composer_file,
				'autoload' => $autoload_file,
			]
		];

		if ('y' === $examples) {
			$person_file = $output_path.'/src/Models/Person.php';
			$department_file = $output_path.'/src/Models/Department.php';

			$this->files->put($person_file, str_replace(array_keys($tokens), array_values($tokens), $customPostType));
			$this->files->put($department_file, str_replace(array_keys($tokens), array_values($tokens), $customTaxonomy));

			$result['files']['person'] = $person_file;
			$result['files']['department'] = $department_file;
		}

		$process = $this->studio("load ".$this->path("/workbench/{$type}s/{$name}"));
		if (!$process->isSuccessful()) {
			exit(8);
		}


		\cli\out("Loading your new plugin into your workbench with Composer...\n");
		$process = $this->composer("require {$tokens['@@COMPOSER_NAME@@']}:\"dev-master\"");
		if (!$process->isSuccessful()) {
			exit(16);
		}
		
		return $result;
	}

	


	

}

