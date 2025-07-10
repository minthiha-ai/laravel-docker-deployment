<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StateRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'SR_Code',
        'SR_Name',
        'SR_Name_MMR',
        'modifiled_by',
        'modifiled_on',
        'active',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;

    public function districts()
    {
        return $this->hasMany(District::class, 'SR_Code', 'SR_Code');
    }

    public function townships()
    {
        return $this->hasMany(Township::class, 'SR_Code', 'SR_Code');
    }
}
