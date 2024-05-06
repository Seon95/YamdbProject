<?php
$logFilePath = 'var/log/dev.log';
$fileHandle = fopen($logFilePath, 'w');
fclose($fileHandle);
