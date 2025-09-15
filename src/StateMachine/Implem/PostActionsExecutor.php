<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\Action\PostActionsExecutorInterface;
use Psr\Log\LoggerInterface;

final readonly class PostActionsExecutor implements PostActionsExecutorInterface
{
    public function __construct(
        private LoggerInterface $postActionsLogger,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function executePostActions(array $postActions, array $parameters): void
    {
        foreach ($postActions as $postAction) {
            try {
                $postAction->run($parameters);
            } catch (\Throwable $exception) {
                $this->postActionsLogger->error(
                    'Error executing post action {postAction}',
                    [
                        'postAction' => get_class($postAction),
                        'exception' => $exception->getMessage(),
                        'parameters' => $parameters,
                        'trace' => $exception->getTraceAsString(),
                    ]
                );
                continue;
            }
        }
    }
}
