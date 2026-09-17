<?php
/**
 * Register and Login are now one combined page. This file only exists so
 * old links (and the quiz-completion "create an account" prompt) keep
 * working - it redirects straight to the Register tab of /login.
 */
require_once __DIR__ . '/includes/config.php';

if (is_learner_logged_in()) {
    redirect(base_url('/my-progress'));
}

redirect(base_url('/login?tab=register'));
