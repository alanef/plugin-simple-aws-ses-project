# Simple AWS SES WordPress Plugin

A WordPress plugin that routes all emails through Amazon SES (Simple Email Service) for reliable email delivery.

## Overview

This repository contains the development environment for the Simple AWS SES WordPress plugin. The actual plugin code is located in the `simple-aws-ses` subdirectory.

## Features

- Easy AWS credential configuration through WordPress admin
- Automatic interception of all WordPress emails
- Support for HTML and plain text emails
- Test email functionality
- Secure credential storage
- Fallback to default mail if SES fails

## Development Setup

### Prerequisites

- PHP 7.2 or higher
- Composer
- Node.js and npm (for development tools)
- WP-CLI (for building releases)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/alanef/plugin-simple-aws-ses-project.git
cd plugin-simple-aws-ses-project
```

2. Install development dependencies:
```bash
composer install
npm install
```

3. Install plugin dependencies:
```bash
cd simple-aws-ses
composer install
cd ..
```

### Local Development

Start the WordPress development environment:
```bash
npm run env:start
```

The plugin will be available at `http://localhost:8888`

Stop the environment:
```bash
npm run env:stop
```

## Building for Release

Build a distributable version of the plugin:
```bash
composer run-script build
```

This creates a clean build in the `dist` directory with:
- Production dependencies only
- No development files
- Optimized autoloader

## Code Quality

### PHP Code Standards

Check code against WordPress coding standards:
```bash
composer run-script phpcs
```

Fix auto-fixable issues:
```bash
composer run-script phpcs-fix
```

### PHP Compatibility

Check compatibility with PHP 7.2+:
```bash
composer run-script phpcompat
```

## GitHub Actions

The project includes automated workflows:

- **Quality Checks** - Runs on all pushes and PRs:
  - Validates composer.json
  - Checks coding standards
  - Verifies PHP compatibility
  - Ensures version consistency
  - Runs WordPress plugin checks

- **Release** - Triggered by version tags (e.g., `v1.0.0`):
  - Runs all quality checks
  - Builds production version
  - Creates GitHub release with zip file

## AWS Configuration

### Prerequisites

1. AWS account with SES access
2. Verified sender email address or domain in SES
3. IAM user with SES permissions

### Required IAM Permissions

Your IAM user needs the following permissions. You can either use the AWS managed policy `AmazonSESSendingAccess` or create a custom policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendRawEmail"
            ],
            "Resource": "*"
        }
    ]
}
```

**Important**: If you're getting authorization errors, ensure:
1. Your IAM user has the correct permissions (check IAM console)
2. The sending email address is verified in SES
3. You're using the correct AWS region where your email is verified
4. If in SES sandbox mode, recipient emails must also be verified

### Plugin Configuration

1. Install and activate the plugin
2. Go to Settings > Simple AWS SES
3. Enter your AWS credentials:
   - Access Key ID
   - Secret Access Key
   - Select your AWS region
4. Configure sender information
5. Send a test email to verify setup

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Ensure all tests pass
5. Submit a pull request

## License

GPL v2 or later. See LICENSE file for details.