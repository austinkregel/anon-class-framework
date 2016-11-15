<?php
/**
 * @var $router
 */
$router->get('/', new class {
    public function serve() {
        return ['hello' => 'There are some cool things here!'];
    }
});
$router->get('/some-page', new class {
    public function serve() {
        return view('views/page.php', ['page' => $_SERVER['REQUEST_URI']]);
    }
});