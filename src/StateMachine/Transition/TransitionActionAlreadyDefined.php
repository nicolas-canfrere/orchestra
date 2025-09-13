<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

final class TransitionActionAlreadyDefined extends \LogicException implements TransitionExceptionInterface
{
}
