<?php
/**
 * JWT signing secret.
 *
 * IMPORTANT: change this to your own long random string before treating
 * this as production-ready. Generate one by running this in Command Prompt:
 *
 *   php -r "echo bin2hex(random_bytes(32));"
 *
 * Then paste the output below, replacing the placeholder value.
 * Keep this value private - anyone with it can forge valid tokens.
 */
define('JWT_SECRET', '46ab3e16e7f078c0434c90161cf7fb98519a612ac621e304909afe871d9f17ef');