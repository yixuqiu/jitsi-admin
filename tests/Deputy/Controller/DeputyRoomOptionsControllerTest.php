<?php

namespace App\Tests\Deputy\Controller;

use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeputyRoomOptionsControllerTest extends WebTestCase
{
    private $client;
    private User $manager;
    private User $deputy;
    private EntityManagerInterface $em;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->manager = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $this->manager->addAddressbook($this->deputy);
        $this->em->persist($this->manager);
        $this->em->flush();

        $this->client->loginUser($this->manager);
        $this->client->request('GET', '/room/deputy/toggle/' . $this->deputy->getUid());
        $this->client->request('GET', '/room/dashboard');

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $this->client->loginUser($deputy);
        $server = $deputy->getServers()->toArray()[0];

        $crawler = $this->client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[moderator]'] = $manager->getId();
        $form['room[name]'] = 'test for the supervisor';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";
        $this->client->submit($form);
    }

    public function testEditConferenceByManager(): void
    {
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'test for the supervisor']);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $this->client->loginUser($manager);


        $crawler = $this->client->request('GET', '/room/new?id=' . $room->getId());
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        self::assertFalse($form->has('room[moderator]'));
        self::assertFalse($form->has('room[server]'));

        $form['room[duration]'] = "45";
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/room/dashboard');

        self::assertEquals(1, $crawler->filter('.snackbar:contains("Die Konferenz wurde erfolgreich bearbeitet")')->count());
        $room = $roomRepo->find($room->getId());
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);

        self::assertEquals($deputy, $room->getCreator());
        self::assertEquals($manager, $room->getModerator());
        self::assertEquals(45, $room->getDuration());
        self::assertEquals(1, sizeof($room->getUser()));
        self::assertEquals($manager, $room->getUser()[0]);
    }

    public function testEditConferenceByDeputy(): void
    {
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'test for the supervisor']);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $this->client->loginUser($deputy);


        $crawler = $this->client->request('GET', '/room/new?id=' . $room->getId());
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        self::assertFalse($form->has('room[moderator]'));
        self::assertTrue($form->has('room[server]'));

        $form['room[duration]'] = "45";
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/room/dashboard');

        self::assertEquals(1, $crawler->filter('.snackbar:contains("Die Konferenz wurde erfolgreich bearbeitet")')->count());
        $room = $roomRepo->find($room->getId());
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);

        self::assertEquals($deputy, $room->getCreator());
        self::assertEquals($manager, $room->getModerator());
        self::assertEquals(45, $room->getDuration());
        self::assertEquals(1, sizeof($room->getUser()));
        self::assertEquals($manager, $room->getUser()[0]);
    }

    public function testAddPArticipantsDeputy(): void
    {
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'test for the supervisor']);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $this->client->loginUser($deputy);

        $crawler = $this->client->request('GET', '/room/participant/add?room=' . $room->getId());

        self::assertEquals(1, $crawler->filter('#atendeeList:contains("Test2, 1234, User2, Test2")')->count());
        $this->client->loginUser($manager);
        $crawler = $this->client->request('GET', '/room/participant/add?room=' . $room->getId());
        self::assertEquals(1, $crawler->filter('#atendeeList:contains("Test2, 1234, User2, Test2")')->count());
    }
}
