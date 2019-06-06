<?php
namespace FusionsPim\Tests\PhpCapsuleListener\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts   = ['tags' => 'array'];
}
