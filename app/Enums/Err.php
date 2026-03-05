<?php

namespace App\Enums;

class Err
{
    const INVALID_PARAMS = [422, 'invalid params'];
    const ERROR = [500, 'error'];
    const REQUIRES_TWO_FACTOR = [403, 'requires_two_factor'];
    
    const UNKNOWN_ERROR = [10000, 'unknown error'];
    const SOPAY_ERROR = [10001, 'sopay error'];
}