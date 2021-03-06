<?php

class Response {

    /**
     * Toutes les valeures a definir dans le header
     * @var Array
     */
    protected $headers;

    /**
     * Version de la requete Http renvoyee par le server
     * @var String
     */
    protected $version;

    /**
     * Code de la reponse
     * @var int
     */
    protected $statusCode;

    /**
     * Encodage de la réponse
     * @var String
     */
    protected $charset;

    /**
     * Contenu de la réponse en dehors du layout
     * @var String
     */
    protected $render;

    /**
     * Toutes les associations entre le code d'une reponse et sa signification
     * @var Array
     */
    public static $statusTexts = [
        200 => 'OK',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error'
    ];

    public function __construct($status = 200, $headers = []) {
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->version = '1.1';
    }

    /**
     * Ajoute le rendu d'une view a render
     * @param  String $path Le chemin juste la view a partir du dossier view
     * Dans le path les / sont remplaces par des points pour rendre la code dans le controller plus clair
     * @param  Array $vars La listes des variables a passer a la vue
     */
    public function view ($path, $vars = [], $helpers = []) {
        $this->render .= self::requireView($path, $vars, $helpers);
    }

    /**
     * Charge une view en injectant des valeures
     * @param  String $path    Chemin de la vue
     * @param  array  $vars    Toutes les valeures à injecter dans la vue
     * @param  array  $helpers Les Helpers à charger
     * @return String          Code Html généré par la view
     */
    public static function requireView($path, $vars = [], $helpers = []) {
        $path = APP . 'views' . DS . str_replace('.', DS, $path) . '.php';
        if (!file_exists($path)) throw new InternalServerException("Ne trouve pas la vue $path");

        ob_start();
        foreach (array_merge($helpers, App::$controller->getAutoLoadHelpers()) as $h => $params) {
            if (is_int($h)) {
                $helper = $params;
                $params = [];
            } else $helper = $h;
            $helperPath = APP . 'helpers' . DS . $helper . 'Helper.php';
            if(!file_exists($helperPath)) throw new InternalServerException("Le fichier $helperPath n'existe pas");

            require_once $helperPath;
            if (!class_exists($helper)) throw new InternalServerException("La classe fille de Helper $helper n'existe pas");

            $r = new ReflectionClass($helper);
            if (!is_array($params)) throw new InternalServerException("Les paramètres passées aux helpers doit être un tableau");

            $$helper = $r->newInstanceArgs($params);
        }
        extract($vars);
        include $path;
        return ob_get_clean();
    }

    /**
     * Affiche le contenu final de la page
     * @return String Le body de la page charge dans les views
     */
    public function render () {
        return $this->render;
    }

    /**
     * Redirige vers une autre url
     * @param  String $url       url de la nouvelle page
     * @param  array  $variables variables a passer dans la session pour la page suivante
     */
    public function redirect ($url) {
        $this->prepare();
        header('Location: http://' . $_SERVER['SERVER_NAME'] . $url, true, $this->statusCode);
        die();
    }

    /**
     * Redirige vers la page précédente
     */
    public function referer () {
        $ref = App::$request->getReferer();
        if ($ref===false) throw new InternalServerException ("Il n'existe pas de requête précédente");
        $this->prepare();
        header('Location: ' . $ref, true, $this->statusCode);
        die();
    }

    /**
     * Prepare la reponse en définissant son header
     */
    public function prepare () {
        $this->charset = ($this->charset) ? $this->charset : 'UTF-8';
        header('Content-Type:text/html;charset='.$this->charset);
        header('HTTP/1.0 '.$this->statusCode.' '.self::$statusTexts[$this->statusCode]);

        foreach ($this->headers as $key => $val) {
            header($key.':'.$val,false,$this->statusCode);
        }

        App::$session->prepare();
    }

    /**
     * Permet de retourner des données sous forme de json puis quitte l'application
     * @param  Array $data  Tableau des données a encoder
     */
    public function json ($data) {
        header('Content-Type:application/json');
        echo json_encode($data);
        die();
    }

    /**
     * Setter pour le statucCode
     * @param int $status Code la de response
     */
    public function setStatusCode (int $status) {
        $this->statusCode = $status;
    }

    /**
     * Setter pour le charset
     * @param String $charset Encodage de la page
     */
    public function setCharset (String $charset) {
        $this->charset = $charset;
    }

    /**
     * Vide le contenu de la page
     */
    public function clear () {
        $this->render = '';
    }

}
