<?php

namespace App\Enums;

class Err
{
    const INVALID_PARAMS = [422, 'invalid params'];
    const ERROR = [500, 'error'];
    
    const UNKNOWN_ERROR = [10000, 'unknown error'];
    const SOPAY_ERROR = [10001, 'sopay error'];
}