<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationCode extends Model
{
    protected $table = "EOFFICE.CARD_TOKEN_ACTIVATION_CODE";
    public $timestamps = false;
    public $primaryKey = "id";
}
