<?php

return [
    // api应用中间件(应用中间件仅在多应用模式下有效)
    'api' => [
        app\common\middleware\ApiPermissions::class,
    ]
];