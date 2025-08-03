<?php

declare(strict_types=1);

namespace Inertia\Configs;

/*
 * |--------------------------------------------------------------------------
 * | History Encryption
 * |--------------------------------------------------------------------------
 * |
 * | Inertia's history encryption protects privileged page data from being
 * | exposed via the browser's back button after logout. When enabled, it
 * | encrypts the current page's state using the browser's SubtleCrypto API
 * | before storing it in the history stack. The encryption key is saved
 * | in sessionStorage. On back navigation, the data is decrypted using
 * | this key. If the key has been cleared (e.g. via `clearHistory()`),
 * | decryption fails and Inertia fetches fresh data from the server.
 * |
 * | Note: Requires a secure context (HTTPS) due to usage of `crypto.subtle`.
 * | For details, visit: https://inertiajs.com/history-encryption
 * |
 */
final class HistoryConfig
{
    public bool $encrypt;

    public function __construct(?bool $encrypt = null)
    {
        $this->encrypt = $encrypt ?? false;
    }
}
