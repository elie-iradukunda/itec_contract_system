<?php
echo "OpenSSL Check:\n";
echo "Enabled: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
echo "Version: " . OPENSSL_VERSION_TEXT . "\n";