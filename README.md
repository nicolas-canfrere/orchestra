# Orchestra

Orchestra is a Symfony-based state machine framework built in PHP 8.4+ that provides workflow orchestration capabilities using object-oriented design patterns with strict interface contracts.

## 🚀 Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (with Docker Compose)
- [Make](https://www.gnu.org/software/make/) (optional, for convenience commands)
- Git

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd orchestra
   ```

2. **Build and start the application:**
   ```bash
   make up
   ```

   Or without Make:
   ```bash
   docker compose up -d --remove-orphans
   ```

3. **Install dependencies:**
   ```bash
   make composer-cli
   composer install
   exit
   ```

4. **Run database migrations:**
   ```bash
   make migrate
   ```

5. **Access the application:**
   - Main application: http://localhost:8000
   - Database: localhost:9876 (PostgreSQL)

That's it! The application should now be running.

## 🏗️ Architecture Overview

Orchestra implements a finite state machine with the following core components:

- **Engine**: Main orchestration engine that executes state transitions
- **ProcessDefinition**: Defines workflow processes with initialization and start states
- **ProcessExecutionContext**: Manages execution context with UUID generation and status tracking
- **State**: Represents workflow states with fluent transition creation
- **Transition**: Manages state-to-state transitions with actions and conditions
- **Action/PostAction**: Executable business logic attached to transitions
- **Condition**: Validation logic for transition eligibility
- **NextTransitionFinder**: Service for finding valid transitions based on conditions

### Key Features

- **Pause-Driven Workflows**: Support for human approval gates and external system integration
- **Database Persistence**: Full execution context storage with PostgreSQL and JSONB
- **Conditional Execution**: Dynamic validation logic for transition eligibility
- **Post-Action Pipeline**: Cleanup and side-effect operations after transitions
- **Error Handling**: Comprehensive exception management and recovery

## 📁 Project Structure

```
src/
├── StateMachine/           # Core state machine implementation
│   ├── Engine/            # Main orchestration engine
│   ├── State/             # State definitions and interfaces
│   ├── Transition/        # Transition logic and finder services
│   ├── Action/            # Action and PostAction interfaces
│   ├── Condition/         # Condition implementations
│   ├── ProcessExecutionContext/  # Process execution management
│   └── Implem/           # Concrete implementations
├── ProcessDefinition/     # Process workflow definitions
└── Controller/           # Symfony API controllers

tests/Unit/               # PHPUnit tests
config/                   # Symfony configuration
migrations/               # Doctrine database migrations
docker/                   # Docker configuration
```

## 🛠️ Development

### Available Commands

#### Application Management
```bash
make up              # Start the application
make down            # Stop the application
make composer-cli    # Access Composer CLI
```

#### Testing
```bash
make test                    # Run all tests
make unit-test              # Run unit tests only
make unit-test R="--filter=EngineTest"  # Run specific test
```

#### Code Quality
```bash
make static-code-analysis   # Run PHPStan analysis
make apply-cs              # Apply coding standards fixes
```

#### Database
```bash
make migrate                # Run Doctrine migrations
make generate-migration     # Generate new migration file
```

### Development Environment

The application runs in a multi-container Docker environment:

- **PHP 8.4**: FPM with Alpine Linux, includes intl, pdo_pgsql, opcache, and xdebug
- **Nginx 1.29**: Web server with reverse proxy configuration
- **PostgreSQL 15**: Database with automatic health checks and initialization

### Environment Configuration

The application uses environment variables defined in `.env`:

```bash
# Database
POSTGRES_DB=orchestra
POSTGRES_USER=orchestra
POSTGRES_PASSWORD=your_password
DATABASE_URL="postgresql://orchestra:password@database:5432/orchestra?serverVersion=15&charset=utf8"

# Symfony
APP_ENV=dev
APP_SECRET=your_secret
```

## 🔌 API Endpoints

### Example Workflow Execution

```bash
# Execute a workflow process
POST http://localhost:8000/example
Content-Type: application/json

{
  "parameter1": "value1",
  "parameter2": "value2"
}
```

Additional examples are available in the `src/Controller/` directory.

## 🧪 Testing

The project includes comprehensive unit tests with PHPUnit:

```bash
# Run all tests
make test

# Run specific test suite
make unit-test

# Run with specific filter
make unit-test R="--filter=EngineTest"
```

Test configuration is defined in `phpunit.dist.xml` with strict error handling enabled.

## 🔍 Code Quality

The project maintains high code quality standards:

- **PHPStan**: Static analysis with strict rules
- **PHP-CS-Fixer**: Automated coding standards enforcement
- **Strict Typing**: All code uses `declare(strict_types=1)`
- **Interface Contracts**: Contract-first development approach

Run quality checks:
```bash
make static-code-analysis && make apply-cs
```

## 🗄️ Database

Orchestra uses PostgreSQL with Doctrine ORM for persistence:

- **UUID-based Process Tracking**: Each process execution has a unique identifier
- **JSONB Storage**: Flexible storage for execution context and transition history
- **Automatic Migrations**: Database schema managed through Doctrine migrations
- **Health Checks**: Automatic database health monitoring in Docker

## 🚀 Production Deployment

1. **Environment Setup**: Configure production environment variables
2. **Build Production Image**: Use multi-stage Dockerfile for optimized builds
3. **Database**: Ensure PostgreSQL is properly configured and secured
4. **Migrations**: Run `make migrate` to apply database schema
5. **Monitoring**: Configure logging and health checks

## 🤝 Contributing

1. Follow the existing code style and architecture patterns
2. Write comprehensive tests for new features
3. Use interface-first development approach
4. Run quality checks before submitting changes:
   ```bash
   make static-code-analysis && make apply-cs && make test
   ```

## 📝 License

This project is proprietary software. Please refer to the license file for more information.