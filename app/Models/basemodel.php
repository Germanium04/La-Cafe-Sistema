<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

abstract class BaseModel
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::connection()->getPdo();
    }
}