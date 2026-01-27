# Testing GitHub Actions Locally with act

This directory contains documentation and configuration for testing GitHub Actions workflows locally using [act](https://github.com/nektos/act).

## What is act?

`act` allows you to run your GitHub Actions workflows locally in Docker containers, making it easy to test changes before pushing to GitHub.

## Installation

### Linux

```bash
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash
```

### macOS

```bash
brew install act
```

### Manual Installation

Download the latest release from the [act releases page](https://github.com/nektos/act/releases).

## Usage

### Run all workflows

```bash
act
```

### Run specific jobs

```bash
# Run only the Pint linting job
act -j pint

# Run only PHPStan analysis
act -j phpstan

# Run only Rector checks
act -j rector

# Run only tests
act -j tests

# Run only frontend linting
act -j frontend
```

### Run specific events

```bash
# Simulate a push event
act push

# Simulate a pull request
act pull_request
```

### Run with specific PHP version

```bash
# Run tests job with PHP 8.4
act -j tests --matrix php-version:8.4

# Run tests job with PHP 8.5
act -j tests --matrix php-version:8.5
```

### Dry run (list jobs without executing)

```bash
act -l
```

### Verbose output

```bash
act -v
```

## Common Issues & Solutions

### Issue: Docker not found

**Solution:** Install Docker Desktop or Docker Engine before using act.

### Issue: Large Docker images

**Solution:** act downloads GitHub Actions runner images which can be large. Use the `-P` flag to specify smaller images:

```bash
act -P ubuntu-latest=catthehacker/ubuntu:act-latest
```

### Issue: Missing secrets/environment variables

**Solution:** Create a `.secrets` file in the project root:

```
EXAMPLE_SECRET=value
```

Then run:

```bash
act --secret-file .secrets
```

### Issue: Permissions errors

**Solution:** Run act with sudo or add your user to the docker group:

```bash
sudo usermod -aG docker $USER
```

Then log out and back in.

## Tips

1. **Use caching:** act supports caching between runs, which speeds up subsequent executions.

2. **Test individual jobs:** When developing, test individual jobs rather than the entire workflow to save time.

3. **Use bindmount for faster iteration:**
   ```bash
   act -b
   ```
   This binds your working directory instead of copying files.

4. **Check workflow syntax:** Before running with act, validate your workflow syntax:
   ```bash
   act --dryrun
   ```

## Workflow-specific Notes

### CI Workflow

Our `ci.yml` workflow has 5 parallel jobs:

- **pint**: Runs code style checks with Laravel Pint
- **phpstan**: Runs static analysis with PHPStan
- **rector**: Runs code quality checks with Rector
- **tests**: Runs the Pest test suite on multiple PHP versions
- **frontend**: Runs ESLint and Prettier checks

All jobs run in parallel by default. To test all jobs:

```bash
act
```

To test a specific area:

```bash
# Just check code style
act -j pint

# Just run tests
act -j tests

# Just check static analysis
act -j phpstan
```

## Resources

- [act Documentation](https://github.com/nektos/act)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitHub Actions Workflow Syntax](https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions)
