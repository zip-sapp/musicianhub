<?php
if (extension_loaded('gd')) {
    echo "GD is installed!";
    echo "<br>GD Version: " . gd_info()['GD Version'];
} else {
    echo "GD is NOT installed!";
}
