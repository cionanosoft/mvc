<?php
namespace Core;

class S_ROUTE {

    private static $safe_module;
    public static $array_parameter = FALSE;

    /**
     * Constructor y el clone ocupa ser vació
     * por efectos de control del Singleton.
     * @private
     */
    private function __construct() {
       
    }

    private function __clone() {
        
    }

    /**
     * Responsability function of the security of the Request URL
     * $this->array_parameter can handle multiples params(-) if the module accept it (RequiereMultipleParams)
     * @return string & Boolean if Module always return a valid module.
     *                   if Parameter will return FALSE if hasn't or Array if multiple.
     */
    private static function CleanURL($caller) {
        $GET = (!empty(explode("?", $_SERVER['REQUEST_URI'])[1]) ? explode("?", $_SERVER['REQUEST_URI'])[1] : NULL);
        if (is_null($GET))
            return $caller == 'Module' ? self::$safe_module : null;
       
        $normalized = rawurlencode(preg_replace('/[^-A-Za-z0-9_. ]/', '', stripslashes(urldecode($GET))));

        // just more security...
        if (S_FNC::strposa($normalized, ['SELECT', 'UPDATE', 'INSERT', 'DELETE', 'DROP', 'WHERE', '1=1']))
            return $caller == 'Module' ? $this->safe_module : null;

        switch ($caller) {
            case 'Module':
                $module = self::__getView($normalized);
                $view = __VIEWS_DIR__ . self::__getView($normalized, 1) . "/" . $module . ".php";
                return file_exists($view) ? $view : self::$safe_module;

            case 'Parameter':
                foreach (($PARAMS = explode("-", $normalized)) as $poc => $val) {
                    if ($poc == 0)
                        continue;
                    
                    array_push(self::$array_parameter, $val);
                }
                return null;
        }
    }

    /** este lo que hace es apoyar al CleanUrl en cuanto a visibilidad
     * no para ser llamado por un usuario
     * 0 retorna Views.folder
     * 1 retorna folder
     * 
     * @param type $normalized
     * @param type $option
     * @return type
     */
    private static function __getView($normalized, $option = 0) {
        return $option === 0 ? explode("-", $normalized)[0] : explode(".", explode("-", $normalized)[0])[0];
    }

    //example of return admin.management  insted of module/admin/admin.management.php
    public static function getParcedModule($view) {
        $split = explode("/", $view);
        return substr(explode("/", $view)[sizeof($split) - 1], 0, -4);
    }

    /**
     * Parameter's CleanURL Inicializator
     * @return A valid or standart Parameter
     */
    public static function getParam($poc) {
        if (!empty(self::$array_parameter)) {
            return isset(self::$array_parameter[$poc]) ? self::$array_parameter[$poc] : FALSE;
        }
        return FALSE;
    }

    public function getParamSize() {
        return sizeof(self::$array_parameter);
    }

    /**
     * Return securelly the Module Sting, detecting and deleting posibles Injects(SQL, XSS)
     * Crea el nuevo controlador 
     */
    public static function Route() 
    {
        self::$safe_module = __VIEWS_DIR__ . "home/home.motd.php";

        $View = self::CleanURL('Module');
        
        require_once __DIR__.'/../App/Controllers/Controller.php';

        $Controller = new \App\Controller\Controller (
                $View,
                self::CleanURL('Parameter') 
        );        
        return $Controller;
    }

}
