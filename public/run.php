<?php
echo "<pre>";
passthru('php ../artisan migrate');
passthru('php ../artisan optimize:clear');
echo"</pre>";