<?php

foreach (glob(dirname(__DIR__, 2).'/var/cache/prod/*.preload.php') ?: [] as $file) {
    require $file;
}
