<?php

namespace Cesa\Document\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Traits\HasNullableCreator;

class Document extends Model
{
    use HasFactory, HasNullableCreator;

    protected $fillable = [
        'title', 'content', 'source_type', 'docx_path',
    ];
}
