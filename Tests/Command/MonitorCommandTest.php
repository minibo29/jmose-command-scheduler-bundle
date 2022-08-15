<?php

namespace JMose\CommandSchedulerBundle\Tests\Command;

use JMose\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

/**
 * Class MonitorCommandTest.
 */
class MonitorCommandTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

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
     * Test scheduler:execute without option.
     */
    public function testExecuteWithError()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->runCommand('scheduler:monitor', ['--dump' => true], true)->getDisplay();

        $this->assertMatchesRegularExpression('/two:/', $output);
        $this->assertMatchesRegularExpression('/four:/', $output);
    }

    /**
     * Test scheduler:execute without option.
     */
    public function testExecuteWithoutError()
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $two = $this->em->getRepository('JMoseCommandSchedulerBundle:ScheduledCommand')->find(2);
        $four = $this->em->getRepository('JMoseCommandSchedulerBundle:ScheduledCommand')->find(4);
        $two->setLocked(false);
        $four->setLastReturnCode(0);
        $this->em->flush();

        // None command should be in error status here.

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->runCommand(
            'scheduler:monitor',
            [
                '--dump' => true,
            ],
            true
        )->getDisplay();

        $this->assertStringStartsWith('No errors found.', $output);
    }
}
