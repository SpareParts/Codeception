<?php
namespace Codeception\Module;

use Codeception\Exception\RemoteException;
use Codeception\Lib\Connector\Guzzle;
use Codeception\Lib\Framework;
use Codeception\TestCase;
use DerpTest\Machinist\Blueprint;
use DerpTest\Machinist\Machinist;
use DerpTest\Machinist\Store\SqlStore;
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
	protected $config = [
		'useDebugApi' => 1,
	];

	/**
	 * @var array
	 */
	protected $requiredFields = ['configMap', 'dbDsn', 'debugApi'];

	/**
	 * @var RobotLoader
	 */
	protected $loader;


	/**
	 * @var \SystemContainer|Container
	 */
	protected $container;


	/**
	 * Obtain configuration from project.
	 */
	public function _initialize()
	{
		// prepare the container to get project configuration
		$this->container = $container = $this->createContainer();

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
	 * @return Container|\SystemContainer
	 */
	public function _getContainer()
	{
		if ($this->container)
		{
			return $this->container;
		}
		$this->container = $container = $this->createContainer();
		return $container;
	}


	/**
	 * Get the fixture path
	 *
	 * @param array $settings
	 * @throws RemoteException
	 */
	public function _beforeSuite($settings = [])
	{
		// dirs
		$this->fixturesDir = $settings['path'] . '_fixtures' . DIRECTORY_SEPARATOR;

		// switch application to test environment
		if ($this->config['useDebugApi'])
		{
			if ($this->curlRequest($url = $this->config['debugApi']['enableTest']))
			{
				throw new RemoteException('Unable to enable `test` mode on remote application using url: '.$url);
			}
		}

		// initialize Machinist

		/** @var Db $dbModule */
		$dbModule = $this->getModule('Db');
		Machinist::store(SqlStore::fromPdo($dbModule->dbh));


		// initialize memcache
		/** @var Memcache $memcacheModule */
//		$memcacheModule = $this->getModule('Memcache');
//		$memcacheModule->_reconfigure([
//			'host' => $this->projectConfig['memcached']['hosts'],
//			'port' => $this->projectConfig['memcached']['port'],
//		]);
	}


	public function _afterSuite($settings = [])
	{
		// turn off the test environment
		if ($this->config['useDebugApi'])
		{
			if ($this->curlRequest($url = $this->config['debugApi']['disableTest']))
			{
				throw new RemoteException('Unable to disable `test` mode on remote application using url: ' . $url);
			}
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
		// clean machinist fixtures
		$this->haveFixtureFactory()->wipeAll(FALSE, ['pricelist_subchannel', 'pricelist_channel']);

		// clean file fixtures
		foreach ($this->undoFixtureFiles as $file) {
			$this->haveFileFixture($file);
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
	 * @return \DerpTest\Machinist\Machinist
	 */
	public function haveFixtureFactory()
	{
		return Machinist::instance();
	}


	/**
	 * @param string|Blueprint $blueprint
	 * @param array $values
	 * @return \DerpTest\Machinist\Machine
	 * @throws \DerpTest\Machinist\MakeException
	 */
	public function createFixture($blueprint, $values = [])
	{
		return Machinist::instance()->blueprint($blueprint)->make($values);
	}


	/**
	 * @param string $fixtureFile
	 * @param string $undoFixtureFile
	 */
	public function haveFileFixture($fixtureFile, $undoFixtureFile = NULL)
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
//		$baseDir = realpath(__DIR__ . '/../../../../../..');
//		$tempDir = $baseDir . $tempDir;
//
//		// config files
//		$files[] = $baseDir . '/Nodus/App/config.neon';
//		$files[] = $baseDir . '/Ulozto/App/config.neon';
//		$files[] = $baseDir . '/Ulozto/App/local-config.neon';
//
//		$configurator = new Configurator();
//		foreach ($files as $file)
//		{
//			$configurator->addConfig($file, 'test');
//		}
//		$configurator->setDebugMode(FALSE);
//		$configurator->setTempDirectory($tempDir);
//		$configurator->addParameters([
//			'tempDir' => $tempDir,
//			'coreAppDir' => $baseDir . '/Nodus/App',
//			'appDir' => $baseDir . '/Ulozto/App',
//			'appDirs' => [$baseDir . '/Nodus/App', $baseDir . '/Ulozto/App'],
//			'coreAppName' => 'Nodus',
//			'appName' => 'Ulozto',
//			'appNamespaces' => ['Nodus', 'Ulozto'],
//		]);
//		$loader = $configurator->createRobotLoader();
//		$loader->addDirectory($baseDir . '/Nodus');
//		$loader->addDirectory($baseDir . '/Ulozto');
//		$loader->addDirectory($baseDir . '/Libs');
//		$loader->register();
//
//		$container = $configurator->createContainer();
//		return $container;
		/**
		 * Ugly hack to make it work the same way our ancient Configurator works.
		 *
		 * @return Container|\SystemContainer
		 */
		$baseDir = realpath(__DIR__ . '/../../../../../..');
		$tempDir = $baseDir . $tempDir;

		// config files
		$files[] = $baseDir . '/Nodus/App/config.neon';
		$files[] = $baseDir . '/Ulozto/App/config.neon';
		$files[] = $baseDir . '/Ulozto/App/local-config.neon';
		$files[] = __DIR__ . '/config.neon';

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
			'container' => [
				'class' => 'SystemContainer'.rand(1, 10000),
				'parent' => 'Nette\DI\Container'
			]
		]);
		$loader = $configurator->createRobotLoader();
		$loader->addDirectory($baseDir . '/Nodus');
		$loader->addDirectory($baseDir . '/Ulozto');
		$loader->addDirectory($baseDir . '/Libs');
		$loader->register();

		$container = $configurator->createContainer();

		$application = $container->application;
		\Nodus\SimpleModel::setContext($container);
		$application->registerModule('Files');
		$application->registerModule('Users');
		$application->registerModule('Search');
		$application->registerModule('Credit');
		$application->registerModule('ExternalStorages');
		$application->registerModule('FileManager');
		$application->registerModule('Messages');
		$application->registerModule('Debug');
		$application->registerModule('Api');
		$application->registerModule('Home');
		$application->registerModule('StaticPages');

		return $container;
	}


	/**
	 * Switch to main web testing
	 */
	public function wantToTestWeb()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('NodusWebDriver');
		$url = $this->config['domains']['web'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to mobile web testing
	 */
	public function wantToTestMobile()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('NodusWebDriver');
		$url = $this->config['domains']['mobile'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to admin web testing
	 */
	public function wantToTestAdmin()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('NodusWebDriver');
		$url = $this->config['domains']['admin'];
		$webdriver->_reconfigure(['url' => $url]);
	}


	/**
	 * Switch to pornfile web testing
	 */
	public function wantToTestPornfile()
	{
		/** @var WebDriver $webdriver */
		$webdriver = $this->getModule('NodusWebDriver');
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
