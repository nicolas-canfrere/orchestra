<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\Transition\TransitionInterface;

final class ExecutedTransition implements \JsonSerializable
{
    private function __construct(
        public readonly TransitionInterface $transition,
        public readonly \DateTimeImmutable $executedAt,
    ) {
    }

    public static function create(TransitionInterface $transition): self
    {
        return new self($transition, new \DateTimeImmutable());
    }

    public function jsonSerialize(): array
    {
        $transition = sprintf(
            '%s::%s',
            $this->transition->getFromState()->getName(),
            $this->transition->getToState()?->getName()
        );

        return [
            'transition' => $transition,
            'executedAt' => $this->executedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
