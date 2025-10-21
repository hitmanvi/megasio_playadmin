<?php

namespace App\Enums;

class Err
{
    const SUCCESS = ['200', 'success'];
    const INVALID_PARAMS = ['422', 'invalid params'];
    const ACCESS_DENIED = ['403', 'access denied'];
    const ACCOUNT_NOT_FOUND = ['404', 'account not found'];
    const ERROR = ['500', 'error'];
    const RECORD_ALREADY_EXISTS = ['400', 'record already exists'];

}