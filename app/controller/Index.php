<?php
namespace app\controller;

use app\common\service\ShortUrl as ShortUrlService;
use support\Request;

class Index
{
    public function redirect(Request $request, $short)
    {
        if (!empty($short)) {
            $urlInfo = ShortUrlService::query($short);

            if (!empty($urlInfo)) {
                ShortUrlService::pvInc($short);
                return redirect($urlInfo['long_url']);
            }
        }
    }
}
