<?php

declare(strict_types=1);

namespace App\Controller;

use App\ProcessDefinition\ExampleProcessDefinition;
use App\StateMachine\Engine\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Example1Controller extends AbstractController
{
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly ExampleProcessDefinition $processDefinition,
    ) {
    }

    #[Route(
        path: '/example1',
        name: 'example1',
        methods: ['POST'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var array<string, mixed> $parameters */
        $parameters = \json_decode($request->getContent(), true);
        $this->engine->launch($this->processDefinition, $parameters);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
