# Contributing to Logstitch

Thank you for considering contributing to Logstitch!

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include as many details as possible using the bug report template.

### Suggesting Features

Feature suggestions are welcome. Please use the feature request template and provide as much detail as possible.

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the test suite (`composer check`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Development Setup

```bash
git clone https://github.com/Carmati-CRM/logstitch.git
cd logstitch
composer install
```

## Running Tests

```bash
# Run all checks (analysis, linting, tests)
composer check

# Run only tests
composer test

# Run static analysis
composer analyse

# Run linters
composer lint

# Fix code style
composer fix
```

## Coding Standards

- Follow PSR-12 coding standard
- Add tests for new features
- Update documentation as needed
- Update CHANGELOG.md for notable changes

## Commit Messages

- Use clear, descriptive commit messages
- Reference issues when applicable (e.g., "Fix #123")

## Creating a New Driver

To create a new driver:

1. Create a class in `src/Driver/` implementing `DriverInterface`
2. Handle all exceptions internally (never throw to caller)
3. Log errors to `error_log()` silently
4. Add tests in `tests/Driver/`
5. Document usage in README.md
