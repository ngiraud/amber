## Documentation

- Every documentated code should be exclusively in English.

## NativePHP Desktop Documentation

- Always use Context7 MCP when I need library/API documentation for NativePHP

## Namespace Imports

- Always import classes with `use` statements at the top of the file
- Never use fully qualified class names (FQCN) in the code body or docblocks

## Eloquent

- Never use `$fillable` or `$guarded` properties. We call `Model::unguard()` in AppServiceProvider and prefer application-wide unguarding.
- Always use the `#[Scope]` attribute on protected methods instead of the `scope` prefix convention

## Eloquent API Resources

- Use `Resource::make()` instead of `new Resource()` in Controllers
- Always pass models to Vue/Inertia via API Resources - never pass raw Eloquent models

## Form Requests

- Always use array syntax for validation rules, not pipe-delimited strings
- Use `Rule::` classes for complex validations

## Authorization

- Define authorization in route definitions using `->can()`, not in controllers or Form Requests

## Actions Pattern

- Controllers MUST delegate all business logic to Action classes
- Actions extend `App\Actions\Action` and implement a `handle()` method
- Actions live in `app/Actions/{Domain}/` (e.g., `app/Actions/Teams/CreateTeam.php`)
- Actions are singletons and use the `Fakeable` trait for testing
- Always inject actions via dependency injection in controller methods, not `Action::make()`
- Use **Verb + Noun** singular naming: `CreateTeam`, `SendMagicLink`, `DeleteUser`
- Create separate actions for distinct operations
- Prefer DTOs over arrays for action input (e.g., `CreateTeamData`)

## Controllers

- Never use try/catch in controllers - let Laravel's exception handler deal with exceptions
- For custom error responses, create custom exception classes

## Inertia Flash Messages

- Use `Inertia::flash()` for one-time notifications
- Separate `Inertia::flash()` call from `return` statement for better type safety

## Attributes

- Use Contextual Attributes whenever possible: `#[CurrentUser]`, etc.

## Finalization

- Before considering a feature complete, run `composer test:all`
- This command runs: lint (Pint + Rector dry-run + ESLint + Prettier), static analysis (PHPStan), tests (Pest)
- Do not commit if this command fails
