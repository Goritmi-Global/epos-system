<?php
echo "<pre>";
passthru('php ../artisan key:generate');
passthru('php ../artisan storage:link');
passthru('php ../artisan optimize:clear');
echo"</pre>";