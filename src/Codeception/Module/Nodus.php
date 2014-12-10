<?php
namespace Codeception\Module;

use Codeception\Lib\Framework;
use Codeception\TestCase;

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
	 * Get the fixture path
	 *
	 * @param array $settings
	 */
	public function _beforeSuite($settings = [])
	{
//		var_dump($settings);
		$this->fixturesDir = $settings['path'] . '_fixtures' . DIRECTORY_SEPARATOR;
	}


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


}
 