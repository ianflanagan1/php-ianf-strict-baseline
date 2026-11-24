<?php

declare(strict_types=1);

// Load-time code: Composer runs it when the autoloader is included, whether or not anything in
// the package is called.
\define('ACME_SETTINGS_LOCALE', (string) getenv('LANG'));
