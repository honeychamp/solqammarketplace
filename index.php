<?php

/**
 * Shared-hosting front controller when the domain document root is the project
 * folder instead of public/. Prefer pointing the domain at /public in cPanel.
 */
require __DIR__ . '/public/index.php';
