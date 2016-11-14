<?php
function cacheFile($path, $file, $params = []){
}
function view($file, $params = []) {
    $path = __DIR__ .'/cache/'.sha1($file).'.php';
    // The cached file doesn't exist, so lets create it ina very gross way.. :)
    if(!file_exists($path) || filemtime($path) < filemtime(__DIR__.'/'.$file)) {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        $page = '<?php $params = ';
        $page .= var_export($params, true);
        $page .= ";\n\n" . 'foreach($params as $key => $value) {$$key = $value;}?>';
        file_put_contents($path, $page.file_get_contents(__DIR__ . '/' . $file));
    }
    // Lets parse the file and then only print out it's contents.
    ob_start();
    require_once $path;
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}
$router = new class {

    protected $routes = [];

    public function get($route, $controller) {
        $this->routes[trim($route, '/')] = $controller;
    }

    public function serve() {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        $controller = $this->routes[$uri];
        if(empty($controller))
        {
            throw new \Exception("Controller [{$uri}] not found!");
        }

        // If the uri is empty that means that the URI is the index page
        return call_user_func_array([$this->routes[$uri], 'serve'], []);
    }
};

    $router->get('/', new class {
        public function serve() {
            return 'hello!';
        }
    });
    $router->get('/some-page', new class {
        public function serve() {
            return view('views/page.php', ['page' => $_SERVER['REQUEST_URI']]);
        }
    });

echo $router->serve();
