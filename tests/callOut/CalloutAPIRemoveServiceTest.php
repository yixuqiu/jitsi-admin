<?php

namespace App\Tests\callOut;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CallOutSessionAPIRemoveService;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class CalloutAPIRemoveServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);

        $calloutSession1 = new CalloutSession();
        $calloutSession1->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($room->getModerator())
            ->setState(CalloutSession::$DIALED)
            ->setUid('ksdlfjlkfds')
            ->setLeftRetries(2);
        $manager->persist($calloutSession1);
        $callerUserId = new CallerId();
        $callerUserId->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setUser($user)
            ->setCallerId('987654321');
        $manager->persist($callerUserId);
        $manager->flush();
    }

    public function testRefuseValid(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (json_decode($update->getData(), true)['type'] === 'snackbar') {
                    self::assertEquals('{"type":"snackbar","message":"AA, 45689, Ldap, LdapUSer hat abgelehnt.","color":"danger","closeAfter":2000}', $update->getData());
                    self::assertEquals(['lobby_moderator/9876543210'], $update->getTopics());
                }
                if (json_decode($update->getData(), true)['type'] === 'refresh') {
                    self::assertEquals('{"type":"refresh","reloadUrl":"\/room\/lobby\/moderator\/a\/9876543210 #waitingUser"}', $update->getData());
                    self::assertEquals(['lobby_moderator/9876543210'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $this->assertSame('test', $kernel->getEnvironment());
        $calloutRemoveService = self::getContainer()->get(CallOutSessionAPIRemoveService::class);
        self::assertEquals(['status' => 'DELETED', 'links' => []], $calloutRemoveService->refuse('ksdlfjlkfds'));
    }


    public function testRefuseinvalid(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $calloutRemoveService = self::getContainer()->get(CallOutSessionAPIRemoveService::class);
        self::assertEquals(['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'], $calloutRemoveService->refuse('invalid'));
    }

    public function testErrorValid(): void
    {
        $kernel = self::bootKernel();
        $directSend = $this->getContainer()->get(DirectSendService::class);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                if (json_decode($update->getData(), true)['type'] === 'snackbar') {
                    self::assertEquals('{"type":"snackbar","message":"Fehler. Melden Sie sich bei Ihrem Support.","color":"danger","closeAfter":2000}', $update->getData());
                    self::assertEquals(['lobby_moderator/9876543210'], $update->getTopics());
                }
                if (json_decode($update->getData(), true)['type'] === 'refresh') {
                    self::assertEquals('{"type":"refresh","reloadUrl":"\/room\/lobby\/moderator\/a\/9876543210 #waitingUser"}', $update->getData());
                    self::assertEquals(['lobby_moderator/9876543210'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $this->assertSame('test', $kernel->getEnvironment());
        $calloutRemoveService = self::getContainer()->get(CallOutSessionAPIRemoveService::class);
        self::assertEquals(['status' => 'DELETED', 'links' => []], $calloutRemoveService->error('ksdlfjlkfds'));
    }




    public function testErrorinvalid(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $calloutRemoveService = self::getContainer()->get(CallOutSessionAPIRemoveService::class);
        self::assertEquals(['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'], $calloutRemoveService->error('invalid'));
    }
}
