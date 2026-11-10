# Security integration checks

Run only against a disposable **local** WordPress installation with this candidate of Site Essentials active. The script creates and removes users, attachments and posts. Never run it on a customer site or a retained development database. Take a snapshot first.

```sh
SE_DISPOSABLE_TESTS=yes php tests/security-regression.php /absolute/path/to/disposable-wordpress
```

The script boots WordPress directly; no PHPUnit dependency or database credentials are bundled. A failed assertion exits unsuccessfully. It covers self-service upload limits, actor permissions, bounded thumbnail sizes, rejected attachment statuses and private parents, simulated option-write failures and rollback, plus same-request write-lock behavior. Run separately on single-site and multisite.

Cross-process lock contention and HTTP nonce failures require separate integration runners; this script does not claim to cover them. Native MySQL/MariaDB advisory locking must be verified in that environment; SQLite uses a real kernel lock rather than the SQLite adapter's dummy GET_LOCK implementation.

# Sicherheitsprüfungen

Ausschließlich in einer entbehrlichen lokalen WordPress-Testinstallation mit aktivem SE-Teststand ausführen. Das Skript legt Benutzer und Inhalte an und entfernt sie wieder. Vorher eine Sicherung erstellen; niemals auf Kundenwebsites oder in zu erhaltenden Entwicklungsdatenbanken ausführen. Aufruf und Grenzen stehen oben. Einzelwebsite und Multisite getrennt prüfen. Die Tests gehören nicht ins installierbare Plugin-ZIP.

The process-level serialization check is also reproducible (Python standard library, no package install):

```sh
SE_DISPOSABLE_TESTS=yes python3 tests/concurrency.py /absolute/path/to/php /absolute/path/to/disposable-wordpress
```

Set `SE_TEST_HOST` if a multisite fixture requires a specific local domain. A writer that cannot acquire its lock is rejected rather than waiting or stealing a timed-out lease. These cooperative locks cover SE writers, not arbitrary database writes by other plugins. MySQL uses connection-bound advisory locks; SQLite uses a zero-content sidecar lock in its database directory, retained to avoid inode replacement races. WP_Filesystem has no kernel-lock equivalent.

Reset failure matrix (disposable local WordPress only): `SE_DISPOSABLE_TESTS=yes php tests/reset-security-regression.php /absolute/disposable/wordpress`. The matrix deletes SE test configuration; snapshot the disposable database first. It verifies individual option/metadata/cron failures, partial-error reporting, retry and revoked maintenance access. Never run against customer data.

MySQL writes require the core wpdb mysqli driver. The guard pins its handle/session and ownership, disables core reconnect retries while the request holds the lock and checks before each SQL statement. On connection loss it fails the request without reacquiring under stale revisions. Custom database drivers require a separately verified contract and currently fail closed. SQLite retains its kernel file lock. Neither path promises an atomic multi-step reset; earlier successful operations may remain after failure.
