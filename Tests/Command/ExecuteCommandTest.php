<?php

namespace JMose\CommandSchedulerBundle\Tests\Command;

use JMose\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

/**
 * Class ExecuteCommandTest.
 */
class ExecuteCommandTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Test scheduler:execute without option.
     */
    public function testExecute()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $output = $this->runCommand('scheduler:execute', [], true)->getDisplay();

        $this->assertStringStartsWith('Start : Execute all scheduled command', $output);
        $this->assertMatchesRegularExpression('/debug:container should be executed/', $output);
        $this->assertMatchesRegularExpression('/Execute : debug:container --help/', $output);
        $this->assertMatchesRegularExpression('/Immediately execution asked for : debug:router/', $output);
        $this->assertMatchesRegularExpression('/Execute : debug:router/', $output);

        $output = $this->runCommand('scheduler:execute')->getDisplay();
        $this->assertMatchesRegularExpression('/Nothing to do/', $output);
    }

    /**
     * Test scheduler:execute without option.
     */
    public function testExecuteWithNoOutput()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $output = $this->runCommand(
            'scheduler:execute',
            [
                '--no-output' => true,
            ],
            true
        )->getDisplay();

        $this->assertEquals('', $output);

        $output = $this->runCommand('scheduler:execute')->getDisplay();
        $this->assertMatchesRegularExpression('/Nothing to do/', $output);
    }

    /**
     * Test scheduler:execute with --dump option.
     */
    public function testExecuteWithDump()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $output = $this->runCommand(
            'scheduler:execute',
            [
                '--dump' => true,
            ],
            true
        )->getDisplay();

        $this->assertStringStartsWith('Start : Dump all scheduled command', $output);
        $this->assertMatchesRegularExpression('/Command debug:container should be executed/', $output);
        $this->assertMatchesRegularExpression('/Immediately execution asked for : debug:router/', $output);
    }
}
