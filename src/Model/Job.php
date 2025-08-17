<?php

namespace App\Model;

use DfTools\SlimOrm\Model;

class Job extends Model
{
    protected $table = 'i_kabar_jobs';
    protected $primaryKey = 'id';
    protected $status;

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }
}
