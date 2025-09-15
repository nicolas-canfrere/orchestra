<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\Implem;

use App\StateMachine\Action\PostActionInterface;
use App\StateMachine\Implem\PostActionsExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PostActionsExecutorTest extends TestCase
{
    private PostActionsExecutor $executor;
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->executor = new PostActionsExecutor($this->logger);
    }

    public function testExecutePostActionsWithEmptyArray(): void
    {
        $this->logger->expects($this->never())->method('error');

        $this->executor->executePostActions([], []);
    }

    public function testExecutePostActionsWithSingleAction(): void
    {
        $postAction = $this->createMock(PostActionInterface::class);
        $parameters = ['key' => 'value'];

        $postAction->expects($this->once())
            ->method('run')
            ->with($parameters);

        $this->logger->expects($this->never())->method('error');

        $this->executor->executePostActions([$postAction], $parameters);
    }

    public function testExecutePostActionsWithMultipleActions(): void
    {
        $postAction1 = $this->createMock(PostActionInterface::class);
        $postAction2 = $this->createMock(PostActionInterface::class);
        $postAction3 = $this->createMock(PostActionInterface::class);
        $parameters = ['userId' => 123, 'action' => 'update'];

        $postAction1->expects($this->once())
            ->method('run')
            ->with($parameters);

        $postAction2->expects($this->once())
            ->method('run')
            ->with($parameters);

        $postAction3->expects($this->once())
            ->method('run')
            ->with($parameters);

        $this->logger->expects($this->never())->method('error');

        $this->executor->executePostActions([$postAction1, $postAction2, $postAction3], $parameters);
    }

    public function testExecutePostActionsHandlesExceptionAndContinues(): void
    {
        $failingAction = $this->createMock(PostActionInterface::class);
        $successfulAction = $this->createMock(PostActionInterface::class);
        $parameters = ['test' => 'data'];

        $exception = new \RuntimeException('Post action failed');

        $failingAction->expects($this->once())
            ->method('run')
            ->with($parameters)
            ->willThrowException($exception);

        $successfulAction->expects($this->once())
            ->method('run')
            ->with($parameters);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Error executing post action {postAction}',
                [
                    'postAction' => get_class($failingAction),
                    'exception' => 'Post action failed',
                    'parameters' => $parameters,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

        $this->executor->executePostActions([$failingAction, $successfulAction], $parameters);
    }

    public function testExecutePostActionsLogsAllExceptions(): void
    {
        $failingAction1 = $this->createMock(PostActionInterface::class);
        $failingAction2 = $this->createMock(PostActionInterface::class);
        $parameters = ['data' => 'test'];

        $exception1 = new \InvalidArgumentException('First error');
        $exception2 = new \LogicException('Second error');

        $failingAction1->expects($this->once())
            ->method('run')
            ->willThrowException($exception1);

        $failingAction2->expects($this->once())
            ->method('run')
            ->willThrowException($exception2);

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->with(
                'Error executing post action {postAction}',
                $this->callback(function (mixed $context) use ($parameters): bool {
                    if (!is_array($context)) {
                        return false;
                    }

                    return isset($context['postAction'])
                           && isset($context['exception'], $context['parameters'])
                           && $context['parameters'] === $parameters
                           && isset($context['trace']);
                })
            );

        $this->executor->executePostActions([$failingAction1, $failingAction2], $parameters);
    }

    public function testExecutePostActionsWithThrowableException(): void
    {
        $postAction = $this->createMock(PostActionInterface::class);
        $parameters = ['test' => 'value'];

        $error = new \Error('Fatal error occurred');

        $postAction->expects($this->once())
            ->method('run')
            ->willThrowException($error);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Error executing post action {postAction}',
                [
                    'postAction' => get_class($postAction),
                    'exception' => 'Fatal error occurred',
                    'parameters' => $parameters,
                    'trace' => $error->getTraceAsString(),
                ]
            );

        $this->executor->executePostActions([$postAction], $parameters);
    }

    public function testExecutePostActionsWithEmptyParameters(): void
    {
        $postAction = $this->createMock(PostActionInterface::class);

        $postAction->expects($this->once())
            ->method('run')
            ->with([]);

        $this->logger->expects($this->never())->method('error');

        $this->executor->executePostActions([$postAction], []);
    }

    public function testExecutePostActionsWithComplexParameters(): void
    {
        $postAction = $this->createMock(PostActionInterface::class);
        $complexParameters = [
            'user' => ['id' => 1, 'name' => 'John'],
            'metadata' => ['timestamp' => time(), 'source' => 'api'],
            'nested' => ['deep' => ['value' => 'test']],
        ];

        $postAction->expects($this->once())
            ->method('run')
            ->with($complexParameters);

        $this->logger->expects($this->never())->method('error');

        $this->executor->executePostActions([$postAction], $complexParameters);
    }

    public function testExecutePostActionsDoesNotStopOnFirstException(): void
    {
        $failingAction = $this->createMock(PostActionInterface::class);
        $successfulAction1 = $this->createMock(PostActionInterface::class);
        $successfulAction2 = $this->createMock(PostActionInterface::class);
        $parameters = ['test' => 'continue'];

        $failingAction->expects($this->once())
            ->method('run')
            ->willThrowException(new \Exception('Middle action failed'));

        $successfulAction1->expects($this->once())
            ->method('run')
            ->with($parameters);

        $successfulAction2->expects($this->once())
            ->method('run')
            ->with($parameters);

        $this->logger->expects($this->once())->method('error');

        $this->executor->executePostActions(
            [$successfulAction1, $failingAction, $successfulAction2],
            $parameters
        );
    }
}
