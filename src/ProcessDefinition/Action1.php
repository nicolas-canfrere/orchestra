<?php

declare(strict_types=1);

namespace App\ProcessDefinition;

use App\StateMachine\Action\ActionInterface;
use Psr\Log\LoggerInterface;

final class Action1 implements ActionInterface
{
    public function __construct(
        private readonly LoggerInterface $orchestraLogger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(array $parameters): void
    {
        $this->orchestraLogger->info('Running action 1...');
    }
}
