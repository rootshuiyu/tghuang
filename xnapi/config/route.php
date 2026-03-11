<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;


//Route::any('/api/make/app[/{params:.*}]', [app\api\controller\Make::class, 'app']);


Route::any('/api/make/app/{params:.*}', [app\api\controller\Make::class, 'app']);

Route::any('/api/pay/app/{params:.*}', [app\api\controller\Pay::class, 'app']);

Route::any('/api/view-res/{path:.+}', [app\api\controller\StaticResource::class, 'serve']);

Route::any('/ev/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/ev2/{params:.*}', [app\api\controller\Pay::class, 'ev2']);

Route::any('/230/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/231/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/232/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/233/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/226/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/227/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/228/{params:.*}', [app\api\controller\Pay::class, 'ev']);

Route::any('/229/{params:.*}', [app\api\controller\Pay::class, 'ev']);






//Route::any('/{params:.*}', [app\api\controller\Pay::class, 'ev']);
