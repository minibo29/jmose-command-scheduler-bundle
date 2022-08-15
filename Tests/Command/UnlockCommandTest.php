<?php

namespace JMose\CommandSchedulerBundle\Tests\Command;

use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;
use JMose\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

/**
 * Class UnlockCommandTest.
 */
class UnlockCommandTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Test scheduler:unlock without --all option.
     */
    public function testUnlockAll()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->runCommand('scheduler:unlock', ['--all' => true], true)->getDisplay();

        $this->assertMatchesRegularExpression('/"two"/', $output);
        $this->assertDoesNotMatchRegularExpression('/"one"/', $output);
        $this->assertDoesNotMatchRegularExpression('/"three"/', $output);

        $this->em->clear();
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'two']);

        $this->assertFalse($two->isLocked());
    }

    /**
     * Test scheduler:unlock with given command name.
     */
    public function testUnlockByName()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->runCommand('scheduler:unlock', ['name' => 'two'], true)->getDisplay();

        $this->assertMatchesRegularExpression('/"two"/', $output);

        $this->em->clear();
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'two']);

        $this->assertFalse($two->isLocked());
    }

    /**
     * Test scheduler:unlock with given command name and timeout.
     */
    public function testUnlockByNameWithTimout()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        // One command is locked in fixture with last execution two days ago (2), another have a -1 return code as lastReturn (4)
        $output = $this->runCommand(
            'scheduler:unlock',
            ['name' => 'two', '--lock-timeout' => 3 * 24 * 60 * 60],
            true
        )->getDisplay();

        $this->assertMatchesRegularExpression('/Skipping/', $output);
        $this->assertMatchesRegularExpression('/"two"/', $output);

        $this->em->clear();
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'two']);

        $this->assertTrue($two->isLocked());
    }
}
