<?php

declare(strict_types=1);

namespace App\Controller;

use App\ProcessDefinition\Example3ProcessDefinition;
use App\StateMachine\Engine\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class Example3ResumeController extends AbstractController
{
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly Example3ProcessDefinition $processDefinition,
    ) {
    }

    #[Route(
        path: '/example3resume/{processId}',
        name: 'example3resume',
        requirements: [
            'processId' => Requirement::UUID_V4,
        ],
        methods: ['POST']
    )]
    public function __invoke(string $processId): Response
    {
        $this->engine->resume($this->processDefinition, $processId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
