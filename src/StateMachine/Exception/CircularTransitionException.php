<?php

declare(strict_types=1);

namespace App\StateMachine\Exception;

use App\StateMachine\Contract\EngineExceptionInterface;

final class CircularTransitionException extends \RuntimeException implements EngineExceptionInterface
{
}
