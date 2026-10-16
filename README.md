# Modestox DeltaLog

**Configurable change tracking and history logging for Magento / OpenMage**

Modestox DeltaLog is a Magento / OpenMage module designed to track changes to selected database-backed entities and fields, store change history, and make recorded changes available through the administrative interface.

The module was created to solve a practical maintenance and traceability problem: identifying what changed, which values were affected, and when the change was recorded.

It focuses on configurable tracking, readable history, and straightforward administration rather than attempting to provide a universal audit system.

## Features

- **Configurable tracking rules** — define which entities, database tables, and fields should be monitored.
- **Field-level configuration** — configure tracked fields and their presentation.
- **Change history** — store previous and updated values for recorded changes.
- **Execution context** — record available contextual information, including timestamps, user information, IP address, and entity identifiers.
- **Administrative interface** — manage tracking rules and browse recorded history.
- **Value presentation** — support field types and value representations configured by the module.
- **Retention management** — configure the retention period and remove old history records through scheduled cleanup.
- **Logging controls** — enable or disable tracking and configure available diagnostic options.

The exact information recorded depends on the tracking configuration and the context in which the underlying entity is saved.

## Why DeltaLog?

In an actively maintained Magento store, changes to product data, configuration-related entities, and other business records can be difficult to trace after the fact.

DeltaLog provides a configurable mechanism for keeping a history of selected changes without requiring a separate, manually maintained process for every tracked field.

The module is intended for situations where built-in platform functionality does not provide the desired level of visibility into changes.

## How It Works

DeltaLog uses Magento / OpenMage model events as the entry point for change tracking.

At a high level, the processing flow is:

1. A tracked model is saved.
2. The module identifies applicable tracking rules.
3. Configured fields are inspected and their previous and current values are evaluated.
4. Detected changes are processed into history entries.
5. The entries are stored in the module's database tables.
6. Administrators can review the recorded history through the backend interface.

The implementation separates event handling, rule compilation, change processing, inspection, and persistence into dedicated components.

This separation keeps the main responsibilities distinct and makes the module easier to maintain.

## Requirements

- Magento 1.x / a compatible OpenMage installation.
- PHP 8.1 or newer, based on the PHP language features used in the current source code.
- A database supported by the target Magento / OpenMage installation.
- Access to the application files and administrative backend.

**Compatibility note:** The exact supported OpenMage release, PHP versions, and database versions have not yet been established through a complete compatibility test matrix. Verify compatibility with your target environment before deploying the module to a production store.

## Installation

The following is the general installation procedure for a Magento 1 / OpenMage module. Confirm the package structure and target installation before deployment.

1. Download or clone this repository.
2. Back up the application files and database.
3. Copy the module files into the corresponding locations in your Magento / OpenMage installation, preserving the directory structure.
4. Verify that the module declaration under `app/etc/modules/` is present and enabled.
5. Clear the Magento application cache.
6. Allow the platform to execute the module's database setup scripts, if required by the package.
7. Verify that the module appears in the administrative interface.
8. Configure the tracking rules and perform a controlled test using a non-production entity.

Do not assume that copying the files alone completes installation. Database setup, permissions, cache state, and platform compatibility must also be checked.

**Recommended:** Perform the first installation and all initial tests in a development or staging environment.

## Configuration

DeltaLog is designed around configurable tracking rules.

Depending on the configured rule and field, the module can be used to define:

- Which database tables or entities should be monitored.
- Which fields should be tracked.
- How tracked fields are interpreted or displayed.
- How recorded changes are presented to administrators.
- How long history entries should be retained.
- Whether tracking and diagnostic logging are enabled.

After creating or changing a rule, verify that it behaves as expected with the actual model and field types used by your application.

Configuration options and their availability may depend on the installed module version.

## Viewing Change History

Recorded history is available through the module's administrative interface.

A history entry can contain information such as:

| Information | Purpose |
|---|---|
| Entity identifier | Identifies the affected record |
| Database table | Identifies the tracked data source |
| Field name | Identifies the changed field |
| Previous value | Represents the recorded value before the change |
| New value | Represents the recorded value after the change |
| Timestamp | Indicates when the change was recorded |
| User information | Provides available information about the acting user |
| IP address | Provides available request-context information |

The fields shown above describe the module's intended history model. The completeness and accuracy of individual values depend on the execution context and the underlying platform lifecycle.

## Data Retention

Change history can accumulate over time, particularly in stores with frequently updated data.

DeltaLog includes configurable retention and scheduled cleanup functionality to help control the volume of stored history.

Before enabling automated cleanup in a production environment:

- Verify the configured retention period.
- Confirm that the platform's scheduled tasks are running.
- Test the cleanup process against old records in a non-production environment.
- Keep independent backups if historical records must be retained for operational or compliance reasons.

The module's history should not be treated as an immutable or tamper-proof audit archive.

## Limitations

DeltaLog is designed for model-based change tracking, not as a database-wide auditing solution.

The following limitations should be considered:

- **Model lifecycle dependency:** Changes made through direct SQL queries or bulk database operations may bypass the model events used by the module.
- **Third-party extensions:** Extensions that modify data through custom persistence mechanisms may require separate validation.
- **Coverage depends on configuration:** Only configured and supported entities and fields are intended to be tracked.
- **Context availability:** User, IP, and other contextual information may not be available in every execution context, such as background jobs.
- **No guarantee of complete audit coverage:** The module has not been established as a security-grade, immutable audit system.
- **Environment-specific behavior:** Compatibility and behavior should be verified against the actual Magento / OpenMage and PHP versions in use.

These limitations are important when deciding whether the module is suitable for a particular store.

## Testing

The source code has undergone a PHP syntax check. This is a basic validation step and does not establish complete functional correctness.

A thorough validation should include:

- Creating a new entity and changing an existing one.
- Updating one field and several fields in a single save.
- Saving an entity without changing its values.
- Testing `NULL`, empty strings, zero, and boolean-like values.
- Testing date fields and select or multiselect attributes where applicable.
- Verifying history behavior when a save operation fails or a transaction is rolled back.
- Testing tracking-rule updates and field removal.
- Verifying scheduled history cleanup.
- Checking administrative permissions and output escaping.
- Confirming behavior with the specific OpenMage release and PHP version used by the target installation.

Automated test coverage and compatibility across multiple environments have not yet been established.

## Security Considerations

Change history may contain sensitive business information or personally identifiable data.

When deploying DeltaLog:

- Restrict administrative access according to the store's access-control policy.
- Avoid tracking passwords, authentication tokens, payment credentials, or other secrets.
- Review which user and request details are retained.
- Apply an appropriate retention policy.
- Keep the application and its dependencies maintained.
- Test administrative output for safe handling of stored values.

The module is intended to provide operational visibility into changes. It should not replace access controls, database backups, security monitoring, or a dedicated audit solution where those are required.

## Project Status

DeltaLog is a practical utility developed to address a real operational need.

Its primary goals are to provide configurable change tracking, convenient access to recorded history, and maintainable integration with Magento / OpenMage.

The module is useful in its intended scenarios, but it has not been verified against every possible entity type, extension, database operation, or deployment configuration.

Contributions, bug reports, and reproducible test cases are welcome.

## Contributing

If you encounter an issue, please provide:

- The Magento / OpenMage version.
- The PHP version.
- The relevant tracking-rule configuration, without sensitive data.
- The steps required to reproduce the issue.
- The expected behavior and the actual result.
- Relevant error messages or logs, with secrets and personal data removed.

Small, focused improvements and reproducible bug reports are particularly useful.

## License

This project is licensed under the MIT License.
See the [LICENSE](LICENSE) file for details.

Copyright (c) 2026 Sergey Kuzmitsky.
---

**Modestox DeltaLog** — a practical approach to configurable change tracking and history logging for Magento / OpenMage.