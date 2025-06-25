# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that routes all emails through Amazon SES. The repository has a dual structure:
- **Root directory**: Development environment with build tools and CI/CD
- **`simple-aws-ses/` subdirectory**: The actual WordPress plugin code

## Essential Commands

### Development Setup
```bash
# Install all dependencies
composer install && npm install && cd simple-aws-ses && composer install && cd ..

# Start local WordPress environment (http://localhost:8414)
npm run env:start

# Run code quality checks
composer run-script phpcs-security  # Security-focused checks only
composer run-script phpcompat       # PHP 7.2+ compatibility check

# Build for release (creates zipped/simple-aws-ses.zip)
composer run-script build
```

### Before Creating a Release
1. Ensure version consistency across:
   - `simple-aws-ses/simple-aws-ses.php` (Version header)
   - `simple-aws-ses/simple-aws-ses.php` (SIMPLE_AWS_SES_VERSION constant)
   - `simple-aws-ses/readme.txt` (Stable tag)
2. Create a git tag matching the version (e.g., `v1.0.0`)
3. Push the tag to trigger automatic GitHub release

## Architecture

### Plugin Structure
The plugin uses PSR-4 autoloading with namespace `SimpleAwsSes\`:
- `Plugin.php` - Singleton entry point, initializes admin and email handler
- `Admin/SettingsPage.php` - WordPress settings API integration, handles AWS credentials
- `Email/MailHandler.php` - Intercepts WordPress emails via `pre_wp_mail` filter
- `Email/SesSender.php` - AWS SES integration using `sendRawEmail` for full email flexibility

### Key Design Decisions
1. **Email Interception**: Uses `pre_wp_mail` filter (WordPress 5.7+) for clean interception
2. **AWS Integration**: Uses `sendRawEmail` instead of `sendEmail` for attachment support and full email control
3. **Fallback Behavior**: Returns `null` from filter to allow WordPress default mail on SES failure
4. **Settings Storage**: AWS credentials stored in WordPress options as `simple_aws_ses_settings`

### AWS SES Implementation Details
- Builds complete MIME-formatted raw emails with proper headers
- Supports multipart messages for attachments
- Base64 encodes message bodies and attachments
- Automatically detects HTML vs plain text content
- Comprehensive error logging with AWS request IDs

## Important Notes

### GitHub Actions
- **checks.yml**: Runs on all pushes/PRs - validates code quality and compatibility
- **release.yml**: Triggered by version tags - builds and creates GitHub releases
- Both workflows require `wp-cli/dist-archive-command` package installation

### Development Constraints
- PHP 7.4+ required (uses null coalescing operator)
- Must maintain WordPress coding standards (PHPCS configured)
- Plugin must work in WordPress 5.0+ environment
- AWS credentials must have `ses:SendRawEmail` permission (NOT `ses:SendEmail`)

### Testing
Test emails can be sent from Settings > Simple AWS SES using the AJAX-powered test button. Check WordPress debug.log for detailed AWS SES responses and errors.