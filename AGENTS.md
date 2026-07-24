# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

## Project Overview

BypassFinals is a PHP library that removes `final` and `readonly` keywords from source code on-the-fly, enabling mocking of final classes and methods for testing purposes. It works by intercepting PHP's file stream operations and modifying code before it's parsed.

## Key Commands

### Testing
```bash
# Run all tests (composer script runs `tester tests -s`)
composer run tester
# or
vendor/bin/tester tests -s

# Run specific test file
vendor/bin/tester tests/BypassFinals/BypassFinals.phpt -s
```

### Static Analysis
```bash
# Run PHPStan static analysis
composer run phpstan
# or
vendor/bin/phpstan analyse
```

### Development Setup
```bash
# Install dependencies
composer install --dev

# Run tests in different PHP versions (see .github/workflows/tests.yml for supported versions)
# Supports PHP 8.2 through 8.5
```

## Architecture Overview

### Core Components

1. **BypassFinals** (`src/BypassFinals.php`) - Main API class that:
   - Manages configuration (allow/deny paths, cache directory)
   - Registers custom stream wrapper
   - Provides token removal logic
   - Tracks debugging information

2. **MutatingWrapper** (`src/MutatingWrapper.php`) - Stream wrapper that:
   - Intercepts file operations for `.php` files
   - Modifies PHP source code on-the-fly if path is allowed
   - Delegates non-PHP operations to underlying wrapper

3. **NativeWrapper** (`src/NativeWrapper.php`) - Native file operations wrapper that:
   - Implements all PHP stream wrapper methods
   - Temporarily restores native protocol for actual file operations
   - Handles file, directory, and metadata operations

4. **PHPUnitExtension** (`src/PHPUnitExtension.php`) - PHPUnit 10+ integration that:
   - Automatically denies the PHPUnit vendor path (`*/vendor/phpunit/*`)
   - Configures BypassFinals from phpunit.xml parameters (`bypassReadOnly`, `bypassFinal`, `cacheDirectory`, `allowPaths`, `denyPaths`; path masks are semicolon-separated)
   - Enables the library during test bootstrap

5. **bootstrap.php** (`src/bootstrap.php`) - Standalone bootstrap that requires the
   wrapper classes and calls `enable()`. Designed to be loaded *before*
   `vendor/autoload.php` so classes registered in autoload files are also processed.

### How It Works

1. **Stream Wrapper Registration**: Replaces PHP's native `file://` protocol handler with `MutatingWrapper`
2. **File Interception**: When PHP loads a `.php` file, `MutatingWrapper` intercepts the operation
3. **Code Modification**: Uses PHP's tokenizer to remove `final` and `readonly` tokens from source
4. **Caching**: Optional filesystem caching of modified code using SHA1 hashes
5. **Path Filtering**: Allow/deny path rules control which files get modified

### Testing Strategy

- **Nette Tester**: Uses `.phpt` files for comprehensive test coverage
- **Fixtures**: Test files in `tests/BypassFinals/fixtures/` demonstrate actual `final` class removal
- **Stream Operations**: Tests verify that custom stream wrapper doesn't break normal file operations
- **Path Filtering**: Tests ensure allow/deny path rules work correctly
- **Caching**: Tests verify cache functionality with hash-based storage
- **Edge Cases**: Tests handle syntax errors, missing files, and other error conditions

### Configuration Patterns

The library supports multiple configuration approaches:

1. **Direct PHP API** (`enable()` defaults: both `$bypassReadOnly` and `$bypassFinal` are `true`):
   ```php
   DG\BypassFinals::enable();
   DG\BypassFinals::allowPaths(['*/src/*']);
   DG\BypassFinals::setCacheDirectory('/tmp/cache');
   ```

2. **PHPUnit XML Extension** (parameters are optional; omitted boolean params default to `true`):
   ```xml
   <extensions>
       <bootstrap class="DG\BypassFinals\PHPUnitExtension">
           <parameter name="bypassFinal" value="true"/>
           <parameter name="bypassReadOnly" value="false"/>
           <parameter name="cacheDirectory" value="./cache"/>
           <parameter name="allowPaths" value="*/src/*;*/lib/*"/>
           <parameter name="denyPaths" value="*/generated/*"/>
       </bootstrap>
   </extensions>
   ```

3. **Standalone bootstrap** (load before `vendor/autoload.php` to also process autoload-registered classes):
   ```php
   // tests/bootstrap.php
   require __DIR__ . '/../vendor/dg/bypass-finals/src/bootstrap.php';
   require __DIR__ . '/../vendor/autoload.php';
   ```

### Performance Considerations

- **Early Initialization**: Must be enabled before classes are loaded
- **Caching**: Use `setCacheDirectory()` to avoid repeated tokenization
- **Path Filtering**: Use allow/deny paths to limit scope and improve performance
- **Stream Wrapper Overhead**: Minimal overhead for non-PHP files due to delegation

### Debugging

Use `DG\BypassFinals::debugInfo()` to troubleshoot issues. This outputs:
- Configuration status (final/readonly bypass enabled)
- Call stack showing where `enable()` was called
- Classes loaded before BypassFinals was started (these cannot be modified)
- List of files that were successfully modified

### Compatibility

- **PHP Versions**: 8.2 through 8.5 (minimum is now `>=8.2`, per `composer.json`)
- **readonly Support**: enabled by default (the 8.2 minimum guarantees the `T_READONLY` token exists)
- **Testing Frameworks**: PHPUnit, Mockery, Nette Tester
- **Internal Classes**: Cannot modify PHP internal classes like `Closure`
