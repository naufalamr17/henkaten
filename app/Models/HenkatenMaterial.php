<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HenkatenMaterial extends Model
{
    use HasFactory;
    use HasUuids;
    
    protected $table = 'henkaten_material';

    protected $guarded = ['id'];
}
