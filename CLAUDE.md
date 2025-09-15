# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Orchestra is a Symfony-based state machine framework built in PHP 8.2+. The core architecture implements a workflow orchestration system using object-oriented design patterns with strict interface contracts.

## Architecture

### State Machine Design
The project implements a finite state machine with the following core components:

- **Engine** (`src/StateMachine/Engine/Engine.php`): Main orchestration engine that launches and executes state transitions with integrated NextTransitionFinder
- **ProcessDefinition** (`src/StateMachine/ProcessDefinition/ProcessDefinitionInterface.php`): Defines workflow processes with initialization and start state management
- **ProcessExecutionContext** (`src/StateMachine/ProcessExecutionContext/`): Manages execution context with UUID generation and status tracking
- **State** (`src/StateMachine/State/State.php`): Represents workflow states with fluent transition creation via `then()` method
- **Transition** (`src/StateMachine/Transition/Transition.php`): Manages state-to-state transitions with action execution, post-actions, and conditions
- **Action** (`src/StateMachine/Action/ActionInterface.php`): Executable business logic attached to transitions via `withAction()` method
- **PostAction** (`src/StateMachine/Action/PostActionInterface.php`): Actions executed after transition completion for cleanup or side effects
- **Condition** (`src/StateMachine/Condition/`): Validation logic for transition eligibility
- **NextTransitionFinder** (`src/StateMachine/Transition/NextTransitionFinder.php`): Service for finding the next valid transition based on conditions

### Key Patterns
- **Interface Segregation**: All components implement focused contracts with interfaces in their respective folders
- **Fluent Builder**: States use fluent interface for transition chaining: `$state->then($nextState)->withAction($action)`
- **Conditional Execution**: Transitions support conditions via `ConditionInterface` for execution eligibility
- **Post-Processing**: Post-actions provide cleanup and side effects after transition completion
- **Immutable Design**: States and transitions are designed with readonly properties where applicable
- **Strict Typing**: All code uses `declare(strict_types=1)` and PHP 8.2+ features
- **Folder-Based Organization**: Components are organized by domain (Engine, State, Transition, etc.) with interfaces alongside implementations

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
- `src/StateMachine/Engine/`: Main orchestration engine and related exceptions
- `src/StateMachine/State/`: State definitions and interfaces
- `src/StateMachine/Transition/`: Transition logic, NextTransitionFinder, and pause functionality
- `src/StateMachine/Action/`: Action and PostAction interfaces and implementations
- `src/StateMachine/Condition/`: Condition implementations for transition validation
- `src/StateMachine/ProcessExecutionContext/`: Process execution context management with persistence
- `src/StateMachine/Implem/`: Concrete implementations (IdGenerator, database writers)
- `src/ProcessDefinition/`: Process workflow definitions and examples
- `src/Controller/`: Symfony controllers for API endpoints
- `tests/Unit/`: PHPUnit tests organized by component
- `config/`: Symfony configuration files including Doctrine setup
- `migrations/`: Doctrine database migration files
- `docker/`: Docker configuration (Nginx, PostgreSQL initialization)
- `docs/`: Project documentation and architectural decisions
- Multi-service Docker environment with PHP 8.4, PostgreSQL 15, and Nginx

## Configuration Files

- `phpunit.dist.xml`: PHPUnit configuration with strict error handling
- `.php-cs-fixer.dist.php`: PHP-CS-Fixer rules
- `phpstan.dist.neon`: PHPStan static analysis configuration
- `compose.yaml` / `compose.test.yaml`: Docker environments for dev/test

## Development Notes

The state machine has evolved into a production-ready workflow orchestration framework with comprehensive features:
- **Pause-Driven Workflows**: Transitions can be marked for pause, enabling human approval gates and external system integration
- **Database Persistence**: Full execution context storage with PostgreSQL and JSONB for flexible querying
- **Conditional Execution**: Validation logic for transition eligibility with dynamic resolution
- **Post-Action Pipeline**: Cleanup and side-effect operations after transition completion
- **Error Handling**: Circular transition detection, action failure recovery, and comprehensive exception management
- **Service Integration**: Dependency injection with Symfony services for all components

The framework supports both simple linear workflows and complex branching processes with conditional logic, making it suitable for business process automation, approval workflows, and long-running orchestrations.

When implementing new features, follow the established contract-first approach by defining interfaces before implementations.

## Recent Development

### Transition Pause Feature (2025-09-14)
The state machine now supports pausing workflows at specific transitions:
- `isPaused()`: Check if a transition is marked for pause
- `withPauseAfterTransition()`: Configure a transition to pause execution after completion
- Engine integration: The execution engine respects pause flags and stops execution when encountered
- Use cases: Long-running workflows, manual approval gates, external system integration points

### Database Integration with Doctrine
The project now includes full database persistence capabilities:
- **Doctrine ORM Integration**: Added Doctrine bundle for ORM capabilities
- **PostgreSQL Support**: Docker compose includes PostgreSQL database service with initialization
- **Database Migration**: `Version20250914065924` creates `process_execution_context` table with UUID support
- **Process Context Persistence**: `DBALProcessExecutionContextWriter` provides database storage for execution contexts
- **JSONB Storage**: Executed transitions stored as JSONB for flexible querying
- **Infrastructure**: Nginx reverse proxy and multi-service Docker environment

### Transition Conditions and Post-Actions
Enhanced transition management includes:
- `ConditionInterface`: Defines validation logic for transition eligibility
- `ValidCondition`: Default always-valid condition implementation
- `NextTransitionFinderInterface`: Service contract for finding next valid transitions
- `NextTransitionFinder`: Implementation with condition-based transition resolution
- Post-actions support for cleanup operations after transition completion

### Process Execution Context
Comprehensive process execution context management:
- `ProcessExecutionContextInterface`: Defines execution context with UUID and status
- `ProcessExecutionContextFactory`: Creates new execution contexts
- `ProcessExecutionContextWriterInterface`: Contract for persisting execution contexts
- `DBALProcessExecutionContextWriter`: Database implementation using Doctrine DBAL
- `SfProcessExecutionContextIdGenerator`: Symfony UUID-based ID generation
- `ProcessExecutionContextStatusEnum`: Tracks context lifecycle (Created, Running, Completed, Failed, Paused)

### Engine Enhancements
The Engine now features:
- **Pause Support**: Execution stops when encountering paused transitions
- Integration with NextTransitionFinder for dynamic transition resolution
- Conditional transition execution based on validation logic
- Post-action execution pipeline for cleanup operations
- Enhanced error handling with action failure recovery
- Database persistence through ProcessExecutionContextWriter
- Parameter passing throughout the execution context

### Infrastructure Improvements
- **Docker Environment**: Multi-service setup with PHP, PostgreSQL, and Nginx
- **Database Schema**: UUID-based process tracking with JSONB transition history
- **Doctrine Migrations**: Automated database schema management
- **Service Configuration**: Symfony services configured for dependency injection

### Architecture Decisions
- All new interfaces follow strict typing with `declare(strict_types=1)`
- Readonly properties used extensively for immutability
- Contract-first development with interface segregation
- UUID-based process identification for traceability
- Database-first persistence with JSONB for flexibility
- Pause-driven workflow control for external integrations
- Separation of concerns between validation, execution, persistence, and post-processing

### Recent Refactoring (2025-09-13)
The codebase has been reorganized into domain-specific folders for better maintainability:
- **Engine folder**: Contains the main Engine class, EngineInterface, and related exceptions (CircularTransitionException)
- **State folder**: Houses State class and StateInterface for workflow state management
- **Transition folder**: Contains Transition class, NextTransitionFinder, and transition-related interfaces
- **Action folder**: Separates ActionInterface and PostActionInterface for better interface segregation
- **Condition folder**: Contains all condition-related logic including AlwaysValidCondition and ConditionInterface
- **ProcessExecutionContext folder**: Comprehensive process context management with factory patterns and status enums
- **Implem folder**: Concrete utility implementations like SfProcessExecutionContextIdGenerator and DBALProcessExecutionContextWriter

This organization improves code discoverability and follows domain-driven design principles.

### Code Quality Improvements (2025-09-15)
The codebase has been enhanced with comprehensive static analysis compliance:
- **PHPStan Compliance**: All code now passes level 9 static analysis without errors
- **Type Safety**: Enhanced type annotations and defensive programming for mixed types
- **Test Quality**: Improved test assertions to avoid redundant type checks
- **Error Prevention**: Proactive type checking in callback functions and complex data structures
- **Quality Gates**: Static analysis integrated into development workflow with make commands

Key improvements include:
- `PostActionsExecutorTest`: Enhanced callback type safety with proper mixed type handling
- `AbstractProcessDefinitionTest`: Removed redundant type assertions for guaranteed return types
- All tests now pass strict PHPStan analysis with proper type guards and defensive programming
