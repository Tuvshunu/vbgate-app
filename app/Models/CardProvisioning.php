<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardProvisioning extends Model
{
    protected $table = "EOFFICE.CARD_TOKEN_PROVISIONING";
    public $timestamps = false;
    public $primaryKey = "id";
}
