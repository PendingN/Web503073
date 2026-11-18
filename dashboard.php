<?php
declare(strict_types=1);

// The overview page was removed; keep old links and bookmarks working.
require_once __DIR__ . '/includes/init.php';

redirect_to('collection.php');
