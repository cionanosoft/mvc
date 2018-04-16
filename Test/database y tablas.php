<?php

use Core\S_DATABASE as sql;
use Core\S_TABLE as table;

$result = sql::execute("SELECT * FROM repositorio");
table::create("test", $cols = sql::getColumns());


foreach (sql::fetch($result) as $row)
{
    echo '<tr>';
    foreach ($cols as $val)
    {
        echo '<td>' . $row[$val] . '</td>';
    }
    echo '</tr>';
}