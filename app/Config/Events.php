<?php

namespace Config;

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        // CloudLinux PHP Selector / LiteSpeed often force zlib.output_compression.
        // CI4 would Whoops; turn it off instead of crashing the storefront.
        @ini_set('zlib.output_compression', '0');
        $value  = ini_get('zlib.output_compression');
        $zlibOn = filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0;

        if (! $zlibOn) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            ob_start(static fn ($buffer) => $buffer);
        }
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
    }
});

Events::on('post_controller_constructor', static function (): void {
    \App\Services\Platform\SchemaHeal::run();
});
