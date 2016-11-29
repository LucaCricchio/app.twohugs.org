<?php
/**
 * @Author: lucac
 *
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class VipPost extends Model
{
    protected $title;

    protected $content;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vip_posts';

}