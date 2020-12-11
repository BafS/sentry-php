<?php

declare(strict_types=1);

namespace Sentry\Tests\Integration;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\Integration\ModulesIntegration;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use function Sentry\withScope;

final class ModulesIntegrationTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider invokeDataProvider
     */
    public function testInvoke(bool $isIntegrationEnabled, bool $expectedEmptyModules): void
    {
        $integration = new ModulesIntegration();
        $integration->setupOnce();

        /** @var ClientInterface&MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('getIntegration')
            ->willReturn($isIntegrationEnabled ? $integration : null);

        SentrySdk::getCurrentHub()->bindClient($client);

        withScope(function (Scope $scope) use ($expectedEmptyModules): void {
            $event = $scope->applyToEvent(new Event(), []);

            $this->assertNotNull($event);

            if ($expectedEmptyModules) {
                $this->assertEmpty($event->getModules());
            } else {
                $this->assertNotEmpty($event->getModules());
            }
        });
    }

    public function invokeDataProvider(): \Generator
    {
        yield [
            false,
            true,
        ];

        yield [
            true,
            false,
        ];
    }

    /**
     * @group legacy
     */
    public function testApplyToEvent(): void
    {
        $this->expectDeprecation('The "Sentry\Integration\ModulesIntegration::applyToEvent" method is deprecated since version 2.4 and will be removed in 3.0.');

        $event = new Event();
        $integration = new ModulesIntegration();
        $integration->applyToEvent($integration, $event);

        $this->assertNotEmpty($event->getModules());
    }
}
