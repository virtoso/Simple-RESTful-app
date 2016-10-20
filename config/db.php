<?php

return [
    'dsn'       => 'mysql:host=localhost;dbname=task_man;charset=utf8',
    'user'      => 'root',
    'password'  => 'root',
    'options'   => [
        PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
    ],
];