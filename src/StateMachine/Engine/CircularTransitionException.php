<?php

declare(strict_types=1);

namespace App\StateMachine\Engine;

final class CircularTransitionException extends \RuntimeException implements EngineExceptionInterface
{
}
