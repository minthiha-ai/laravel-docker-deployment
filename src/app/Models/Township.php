<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Township extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'SR_Code',
        'D_Code',
        'TS_Code',
        'TS_Name',
        'TS_Name_MMR',
        'modifiled_by',
        'modifiled_on',
        'active',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;

    // Relationships
    public function stateRegion()
    {
        return $this->belongsTo(StateRegion::class, 'SR_Code', 'SR_Code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'D_Code', 'D_Code');
    }
}
