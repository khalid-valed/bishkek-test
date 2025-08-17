<?php

namespace App\Controller;

use DfTools\SlimOrm\DB;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use App\Model\Job;

class IKabarJobsController
{
    private const FILE_DONE = 'DONE';

    public function __construct()
    {
        $pdo = new \PDO('sqlite:'. ROOT_DIR.'/database/jobs.db');
        DB::init($pdo);
    }

    public function getJobs(Request $request, Response $response)
    {
        $type = $request->getQueryParam('type', 'CREATED');

        if ($type === strtolower(self::FILE_DONE)) {
            $res = DB::table('i_kabar_jobs')
            ->orWhere('status', '=', self::FILE_DONE)
            ->paginate(20);
        } else {
            $res = DB::table('i_kabar_jobs')
            ->orWhere('status', '!=', self::FILE_DONE)
            ->paginate(20);
        }

        return $response->withJson($res);
    }

    public function createJobs(Request $request, Response $response)
    {
        Job::insert([
           'url' => $request->getParam('url'),
           'phone' => $request->getParam('phone'),
           'tags' => $request->getParam('tags'),
           'title' => $request->getParam('title'),
           'thumbnail' => $request->getParam('thumbnail'),
        ]);

        return $response->withJson($request->getParsedBody());
    }
}
