<?php

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

function missingPage() {
    return new class {
        public function serve() {
            return [
                'message' => 'The page you requested has gone missing!',
                'code' => sha1($_SERVER['REQUEST_URI'].time()),
                'status' => 404
            ];
        }
    };
}
$database = new class extends PDO {

};
/**
 * @var $router
 */
$router = new class {

    protected $routes = [];

    protected $types = [];
    public function __call($name, $arguments)
    {
        $types = ['get','delete','post','put','head'];
        if(in_array($name, $types)){
            list($route, $controller) = $arguments;
            $this->routes[trim($route,'/')] = $controller;
            $this->types[trim($route, '/')] = $name;
        }
    }

    public function serve() {
        // We must grab the requested URI to later compare against our current routes.
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        // We also must grab the method of which we request.
        $method = $_SERVER['REQUEST_METHOD'];
        // Now we must compare how we're getting the data to what
        // Our app is allowing.
        if($this->types[$uri] === $method) {
            $this->routes[$uri] = missingPage();
        }

        $controller = $this->routes[$uri];
        if(empty($controller))
        {
            $this->routes[$uri] = missingPage();
        }

        // If the uri is empty that means that the URI is the index page
        return new class(call_user_func_array([$this->routes[$uri], 'serve'], [])) {
            protected $result;
            public function __construct($result)
            {
                $this->result = $result;
            }

            public function __toString()
            {
                if(is_array($this->result)){
                    header('Content-type: application/json');
                    if($this->result['status']){
                        http_response_code($this->result['status']);
                    }
                    return json_encode($this->result);
                } else if(is_object($this->result)){
                    header('Content-type: application/json');
                    return json_encode($this->result->toArray());
                }
                return $this->result;
            }
        };
    }
};
include 'api.php';

echo $router->serve();
