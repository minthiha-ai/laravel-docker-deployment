<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'D_Code',
        'D_Name',
        'D_Name_MMR',
        'SR_Code',
        'modifiled_by',
        'modifiled_on',
        'active',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;

    public function stateRegion()
    {
        return $this->belongsTo(StateRegion::class, 'SR_Code', 'SR_Code');
    }

    public function townships()
    {
        return $this->hasMany(Township::class, 'D_Code', 'D_Code');
    }
}
