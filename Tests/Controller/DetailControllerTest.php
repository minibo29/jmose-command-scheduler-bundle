<?php

namespace JMose\CommandSchedulerBundle\Tests\Controller;

use JMose\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

/**
 * Class DetailControllerTest.
 */
class DetailControllerTest extends WebTestCase
{

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        self::bootKernel();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }
    /**
     * Test "Create a new command" button.
     */
    public function testInitNewScheduledCommand()
    {
        $client = parent::createClient();
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $crawler = $client->request('GET', '/command-scheduler/detail/new');
        $this->assertEquals(1, $crawler->filter('button[id="command_scheduler_detail_save"]')->count());
    }

    /**
     * Test "Edit a command" action.
     */
    public function testInitEditScheduledCommand()
    {
        $client = parent::createClient();
        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $crawler = $client->request('GET', '/command-scheduler/detail/edit/1');
        $this->assertEquals(1, $crawler->filter('button[id="command_scheduler_detail_save"]')->count());

        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();
        $fixtureSet = [
            'command_scheduler_detail[id]' => '1',
            'command_scheduler_detail[name]' => 'one',
            'command_scheduler_detail[command]' => 'debug:container',
            'command_scheduler_detail[arguments]' => '--help',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => 'one.log',
            'command_scheduler_detail[priority]' => '100',
            'command_scheduler_detail[save]' => '',
        ];

        $this->assertEquals($fixtureSet, $form->getValues());
    }

    /**
     * Test new scheduling creation.
     */
    public function testNewSave()
    {
        $client = parent::createClient();

        $this->databaseTool->loadAliceFixture([]);

        $client->followRedirects(true);
        $crawler = $client->request('GET', '/command-scheduler/detail/new');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->setValues([
            'command_scheduler_detail[name]' => 'wtc',
            'command_scheduler_detail[command]' => 'translation:update',
            'command_scheduler_detail[arguments]' => '--help',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => 'wtc.log',
            'command_scheduler_detail[priority]' => '5',
        ]);
        $crawler = $client->submit($form);

        $this->assertEquals(1, $crawler->filter('a[href^="/command-scheduler/action/toggle/"]')->count());
        $this->assertEquals('wtc', trim($crawler->filter('td')->eq(1)->text()));
    }

    /**
     * Test "Edit and save a scheduling".
     */
    public function testEditSave()
    {
        $client = parent::createClient();

        // DataFixtures create 4 records
        $this->databaseTool->loadAliceFixture([LoadScheduledCommandData::class]);

        $client->followRedirects(true);
        $crawler = $client->request('GET', '/command-scheduler/detail/edit/1');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->get('command_scheduler_detail[name]')->setValue('edited one');
        $crawler = $client->submit($form);

        $this->assertEquals(4, $crawler->filter('a[href^="/command-scheduler/action/toggle/"]')->count());
        $this->assertEquals('edited one', trim($crawler->filter('td')->eq(1)->text()));
    }
}
