<?php

declare(strict_types=1);

namespace App\StateMachine\Engine;

final class ResumeForbiddenException extends \LogicException implements EngineExceptionInterface
{
}
