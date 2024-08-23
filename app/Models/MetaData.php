<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaData extends Model
{
    protected $table = "EOFFICE.CARD_TOKEN_META_DATA";
    public $timestamps = false;
    public $primaryKey = "id";
}
