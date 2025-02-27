<?php
// load config
foreach (glob("../config/*.php") as $filename)
{
    require_once $filename;
}

// load helper
foreach (glob("../helper/*.php") as $filename)
{
    require_once $filename;
}

?>