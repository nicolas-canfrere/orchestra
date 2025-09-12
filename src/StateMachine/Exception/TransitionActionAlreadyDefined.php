<?php

declare(strict_types=1);

namespace App\StateMachine\Exception;

use App\StateMachine\Contract\TransitionExceptionInterface;

final class TransitionActionAlreadyDefined extends \LogicException implements TransitionExceptionInterface
{
}
