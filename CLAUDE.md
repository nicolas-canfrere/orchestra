# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Orchestra is a Symfony-based state machine framework built in PHP 8.2+. The core architecture implements a workflow orchestration system using object-oriented design patterns with strict interface contracts.

## Architecture

### State Machine Design
The project implements a finite state machine with the following core components:

- **Engine** (`src/StateMachine/Engine.php`): Main orchestration engine that launches and executes state transitions with integrated NextTransitionFinder
- **ProcessDefinition**: Defines workflow processes with initialization and start state management
- **ProcessExecutionContext**: Manages execution context with UUID generation and status tracking
- **State** (`src/StateMachine/State.php`): Represents workflow states with fluent transition creation via `then()` method
- **Transition** (`src/StateMachine/Transition.php`): Manages state-to-state transitions with action execution, post-actions, and conditions
- **Action**: Executable business logic attached to transitions via `withAction()` method
- **PostAction**: Actions executed after transition completion for cleanup or side effects
- **Condition** (`src/StateMachine/Condition/`): Validation logic for transition eligibility
- **NextTransitionFinder** (`src/StateMachine/NextTransitionFinder.php`): Service for finding the next valid transition based on conditions

### Key Patterns
- **Interface Segregation**: All components implement focused contracts in `src/StateMachine/Contract/`
- **Fluent Builder**: States use fluent interface for transition chaining: `$state->then($nextState)->withAction($action)`
- **Conditional Execution**: Transitions support conditions via `ConditionInterface` for execution eligibility
- **Post-Processing**: Post-actions provide cleanup and side effects after transition completion
- **Immutable Design**: States and transitions are designed with readonly properties where applicable
- **Strict Typing**: All code uses `declare(strict_types=1)` and PHP 8.2+ features

## Development Commands

### Testing
```bash
# Run all unit tests
make unit-test

# Run specific test with filter
make unit-test R="--filter=EngineTest"
```

### Code Quality
```bash
# Static analysis with PHPStan
make static-code-analysis

# Apply coding standards fixes
make apply-cs

# Combined analysis
make static-code-analysis && make apply-cs
```

### Environment Setup
```bash
# Access Composer CLI in Docker
make composer-cli
```

## Project Structure

- `src/StateMachine/`: Core state machine implementation
- `src/StateMachine/Contract/`: Interface definitions for all components
- `src/StateMachine/Condition/`: Condition implementations for transition validation
- `src/StateMachine/Implem/`: Concrete implementations (ProcessExecutionContext, IdGenerator)
- `src/StateMachine/ProcessExecutionContext/`: Process execution context management
- `tests/Unit/`: PHPUnit tests organized by component
- `config/`: Symfony configuration files
- `docs/`: Project documentation and architectural decisions
- Docker-based development environment with Composer 2

## Configuration Files

- `phpunit.dist.xml`: PHPUnit configuration with strict error handling
- `.php-cs-fixer.dist.php`: PHP-CS-Fixer rules
- `phpstan.dist.neon`: PHPStan static analysis configuration
- `compose.yaml` / `compose.test.yaml`: Docker environments for dev/test

## Development Notes

The state machine has evolved beyond the initial development phase with a fully functional execution engine that supports:
- Conditional transition execution with validation logic
- Post-action execution for cleanup and side effects
- Circular transition detection and error handling
- Integration with NextTransitionFinder service for dynamic transition resolution

When implementing new features, follow the established contract-first approach by defining interfaces before implementations.

## Recent Development

### Transition Conditions and Post-Actions
Recent additions include enhanced transition management:
- `ConditionInterface`: Defines validation logic for transition eligibility
- `ValidCondition`: Default always-valid condition implementation
- `NextTransitionFinderInterface`: Service contract for finding next valid transitions
- `NextTransitionFinder`: Implementation with condition-based transition resolution
- Post-actions support for cleanup operations after transition completion

### Process Execution Context
The project includes comprehensive process execution context management:
- `ProcessExecutionContextInterface`: Defines execution context with UUID and status
- `ProcessExecutionContextFactory`: Creates new execution contexts
- `SfProcessExecutionContextIdGenerator`: Symfony UUID-based ID generation
- `ProcessExecutionContextStatusEnum`: Tracks context lifecycle (Created, Running, Completed, Failed)

### Engine Enhancements
The Engine now features:
- Integration with NextTransitionFinder for dynamic transition resolution
- Conditional transition execution based on validation logic
- Post-action execution pipeline for cleanup operations
- Enhanced error handling with action failure recovery
- Parameter passing throughout the execution context

### Architecture Decisions
- All new interfaces follow strict typing with `declare(strict_types=1)`
- Readonly properties used extensively for immutability
- Contract-first development with interface segregation
- UUID-based process identification for traceability
- Separation of concerns between validation, execution, and post-processing
