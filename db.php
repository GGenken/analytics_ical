<?php

/***************************************************
 * Данный скрипт должен быть в папке, не доступной *
 * извне, но доступной для ЛК (см. .htaccess)      *
 ***************************************************/

error_reporting(0); # Подавление ошибок, т. к. их обработчики нормально работают
$connectionDB = mysqli_connect( # Подключение
    "localhost",       # IP
    "cal",             # User
    "cal",          # Password
    "cal"           # DB
);

# Если есть ошибки с подключением к БД, завершаем выполнение скрипта
if (mysqli_connect_errno()) { echo(json_encode(['status' => 'error', 'code' => 1, 'details' => ['db_error_code' => mysqli_connect_errno(), 'description' => mysqli_connect_error()]])); die(1); } // Проверка подключения