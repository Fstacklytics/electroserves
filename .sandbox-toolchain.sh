#!/bin/sh
# Sandbox toolchain bootstrap for this workspace.
#
# This environment cannot reach Packagist, deb.debian.org, or packages.sury.org,
# so there is no system PHP and `composer install` cannot hit the default repo.
# The workarounds below are LOCAL TO THE SANDBOX ONLY and are not part of the
# application's production setup (composer.json still targets Packagist).
#
#   php      -> PHP 8.3 (WASM) CLI shim  : /home/user/.phpwasm/php-cli.mjs
#   composer -> official composer.phar   : /tmp/composer.phar (sha256 verified)
#   packages -> prebuilt zip artifacts   : /home/user/php-artifacts/artifacts
#
# Usage:  . ./.sandbox-toolchain.sh
export PATH=/home/user/bin:$PATH
export COMPOSER_HOME=/home/user/.composer-sandbox
alias composer='php /tmp/composer.phar'
