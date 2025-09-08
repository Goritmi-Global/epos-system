<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BaseModel extends Model
{ 
    protected array $baseAppends = ['formatted_created_at', 'formatted_updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // merge BaseModel appends with child model appends
        $this->appends = array_values(array_unique(array_merge($this->appends, $this->baseAppends)));
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('d-M-Y : h:iA')
            : null;
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at
            ? Carbon::parse($this->updated_at)->format('d-M-Y : h:iA')
            : null;
    }
}
