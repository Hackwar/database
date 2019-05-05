<?php
/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Tests\Command;

use Joomla\Console\Application;
use Joomla\Database\Command\ImportCommand;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseImporter;
use Joomla\Database\Exception\UnsupportedAdapterException;
use Joomla\Database\Tests\Cases\MysqlCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test class for \Joomla\Database\Command\ImportCommand
 */
class ImportCommandTest extends MysqlCase
{
	/**
	 * Path to the database stubs
	 *
	 * @var  null|string
	 */
	private $stubPath = null;

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @return  void
	 */
	public static function setUpBeforeClass()
	{
		if (!\defined('JPATH_ROOT'))
		{
			self::markTestSkipped('Constant `JPATH_ROOT` is not defined.');
		}

		parent::setUpBeforeClass();
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->stubPath = dirname(__DIR__) . '/Stubs/Importer';
	}

	public function testTheDatabaseIsImportedWithAllTables()
	{
		$input  = new ArrayInput(
			[
				'command'  => 'database:import',
				'--all'    => true,
				'--folder' => $this->stubPath,
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new ImportCommand(static::$driver);
		$command->setApplication($application);

		$this->assertSame(0, $command->execute($input, $output));

		$screenOutput = $output->fetch();
		$this->assertContains('Import completed in', $screenOutput);
	}

	public function testTheDatabaseIsImportedWithASingleTable()
	{
		$input  = new ArrayInput(
			[
				'command'  => 'database:import',
				'--table'  => 'dbtest',
				'--folder' => $this->stubPath,
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new ImportCommand(static::$driver);
		$command->setApplication($application);

		$this->assertSame(0, $command->execute($input, $output));

		$screenOutput = $output->fetch();
		$this->assertContains('Import completed in', $screenOutput);
	}

	public function testTheCommandFailsIfTheDatabaseDriverDoesNotSupportImports()
	{
		$db = $this->createMock(DatabaseDriver::class);
		$db->expects($this->once())
			->method('getImporter')
			->willThrowException(new UnsupportedAdapterException('Testing'));

		$db->expects($this->once())
			->method('getName')
			->willReturn('test');

		$input  = new ArrayInput(
			[
				'command'  => 'database:import',
				'--folder' => $this->stubPath,
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new ImportCommand($db);
		$command->setApplication($application);

		$this->assertSame(1, $command->execute($input, $output));

		$screenOutput = $output->fetch();
		$this->assertContains('The "test" database driver does not', $screenOutput);
	}

	public function testTheCommandFailsIfRequiredOptionsAreMissing()
	{
		$input  = new ArrayInput(
			[
				'command'  => 'database:import',
				'--folder' => $this->stubPath,
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new ImportCommand(static::$driver);
		$command->setApplication($application);

		$this->assertSame(1, $command->execute($input, $output));

		$screenOutput = $output->fetch();
		$this->assertContains('Either the --table or --all option', $screenOutput);
	}

	public function testTheCommandFailsIfTheRequestedTableDoesNotHaveAnImportFile()
	{
		$importer = $this->createMock(DatabaseImporter::class);
		$importer->expects($this->once())
			->method('withStructure')
			->willReturnSelf();

		$importer->expects($this->once())
			->method('asXml')
			->willReturnSelf();

		$db = $this->createMock(DatabaseDriver::class);
		$db->expects($this->once())
			->method('getImporter')
			->willReturn($importer);

		$input  = new ArrayInput(
			[
				'command'  => 'database:import',
				'--table'  => 'dbtest',
				'--folder' => dirname($this->stubPath),
			]
		);
		$output = new BufferedOutput;

		$application = new Application($input, $output);

		$command = new ImportCommand($db);
		$command->setApplication($application);

		$this->assertSame(1, $command->execute($input, $output));

		$screenOutput = $output->fetch();
		$this->assertContains('The dbtest.xml file does not exist.', $screenOutput);
	}
}
