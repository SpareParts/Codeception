<?php
namespace Codeception\Module;

use Codeception\Exception\RemoteException;
use Codeception\Lib\Connector\Guzzle;
use Codeception\Lib\Framework;
use Codeception\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Url;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\Loaders\RobotLoader;

/**
 * @author Ondrej Hatala <ondriq.h@gmail.com>
 * @since 9.12.2014 10:53
 * @version 1.0
 */ 
class Nodus extends Framework
{

	/**
	 * @var string
	 */
	protected $fixturesDir;

	/**
	 * @var array
	 */
	protected $undoFixtureFiles = [];

	/**
	 * @var array
	 */
	protected $projectConfig = [];

	/**
	 * @var array
	 */
	protected $requiredFields = ['configMap', 'dbDsn', 'debugApi'];

	/**
	 * @var RobotLoader
	 */
	protected $loader;


	/**
	 * Obtain configuration from project.
	 */
	public function _initialize()
	{
		// prepare the container to get project configuration
		$container = $this->createContainer();

		// settings from project config
		foreach ($this->config['configMap'] as $map)
		{
			if (is_string($m = $map))
			{
				$map['source'] = $map['target'] = $m;
			}
			$this->projectConfig[$map['target']] = $container->getParam($map['source']);
		}

		// db connection
		/** @var Db $dbModule */
		$dbModule = $this->getModule('Db');
		$dbModule->_reconfigure([
			'dsn' => 'mysql:host='.$this->projectConfig['db']['host'].';dbname='.$this->projectConfig['db']['database'],
			'user' => $this->projectConfig['db']['username'],
			'password' => $this->projectConfig['db']['password'],
		]);

		parent::_initialize();
	}


	/**
	 * Get the fixture path
	 *
	 * @param array $settings
	 */
	public function _beforeSuite($settings = [])
	{
		// dirs
		$this->fixturesDir = $settings['path'] . '_fixtures' . DIRECTORY_SEPARATOR;

		// switch application to test environment
		if ($this->curlRequest($url = $this->config['debugApi']['enableTest']))
		{
			throw new RemoteException('Unable to enable `test` mode on remote application using url: '.$url);
		}
	}


	public function _afterSuite($settings = [])
	{
		// turn off the test environment
		if ($this->curlRequest($url = $this->config['debugApi']['disableTest']))
		{
			throw new RemoteException('Unable to disable `test` mode on remote application using url: '.$url);
		}
	}


	/**
	 * Prepare test environment.
	 *
	 * @param TestCase $testCase
	 */
	public function _before(TestCase $testCase)
	{
		$this->undoFixtureFiles = [];
	}


	public function _after(TestCase $testCase)
	{
		foreach ($this->undoFixtureFiles as $file) {
			$this->haveFixture($file);
		}
		$this->undoFixtureFiles = [];
	}


	/**
	 * @param string $url
	 *
	 * @return array json
	 */
	protected function curlRequest($url)
	{
		$guzzle = new Client();
		$response = $guzzle->get($url);
		return (bool) (substr($response->getBody(), 0, 2) !== 'OK');
	}


	/**
	 * @param string $fixtureFile
	 * @param string $undoFixtureFile
	 */
	public function haveFixture($fixtureFile, $undoFixtureFile = NULL)
	{
		/** @var Db $dbModule */
		$dbModule = $this->getModule('Db');

		$sql = file($this->fixturesDir . $fixtureFile);

		$dbModule->driver->load($sql);

		if ($undoFixtureFile) {
			$this->undoFixtureFiles[] = $undoFixtureFile;
		}
	}




	/**
	 * Ugly hack to make it work the same way our ancient Configurator works.
	 *
	 * @return Container|\SystemContainer
	 */
	protected function createContainer($tempDir = '/Tests/_temp')
	{
		$baseDir = realpath(__DIR__ . '/../../../../../..');
		$tempDir = $baseDir . $tempDir;

		// config files
		$files[] = $baseDir . '/Nodus/App/config.neon';
		$files[] = $baseDir . '/Ulozto/App/config.neon';
		$files[] = $baseDir . '/Ulozto/App/local-config.neon';

		$configurator = new Configurator();
		foreach ($files as $file)
		{
			$configurator->addConfig($file, 'test');
		}
		$configurator->setDebugMode(FALSE);
		$configurator->setTempDirectory($tempDir);
		$configurator->addParameters([
			'tempDir' => $tempDir,
			'coreAppDir' => $baseDir . '/Nodus/App',
			'appDir' => $baseDir . '/Ulozto/App',
			'appDirs' => [$baseDir . '/Nodus/App', $baseDir . '/Ulozto/App'],
			'coreAppName' => 'Nodus',
			'appName' => 'Ulozto',
			'appNamespaces' => ['Nodus', 'Ulozto'],
		]);
		$loader = $configurator->createRobotLoader();
		$loader->addDirectory($baseDir . '/Nodus');
		$loader->addDirectory($baseDir . '/Ulozto');
		$loader->addDirectory($baseDir . '/Libs');
		$loader->register();

		$container = $configurator->createContainer();
		return $container;
	}


	/**
	 * Switch to main web testing
	 */
	public function wantToTestWeb()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('WebDriver');
		$url = $this->config['domains']['web'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to mobile web testing
	 */
	public function wantToTestMobile()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('WebDriver');
		$url = $this->config['domains']['mobile'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to admin web testing
	 */
	public function wantToTestAdmin()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('WebDriver');
		$url = $this->config['domains']['admin'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to pornfile web testing
	 */
	public function wantToTestPornfile()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('WebDriver');
		$url = $this->config['domains']['pornfile'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * @param array|string $linesToAddToConfig
	 */
	public function alterApplicationConfig($linesToAddToConfig = [])
	{
		$data = (is_array($linesToAddToConfig)) ? implode(PHP_EOL, $linesToAddToConfig) : $linesToAddToConfig;

		$url = \GuzzleHttp\Url::fromString($this->config['debugApi']['enableTest']);
		$url->getQuery()->set('config_data', $data);
		$this->curlRequest($url);
	}


}
