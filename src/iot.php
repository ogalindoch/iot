<?php

namespace euroglas\iot;

class iot implements \euroglas\eurorest\restModuleInterface
{
    // Nombre oficial del modulo
    public function name() { return "iot"; }

    // Descripcion del modulo
    public function description() { return "Acceso a la informacion de Internet Of Things"; }

    // Regresa un arreglo con los permisos del modulo
    // (Si el modulo no define permisos, debe regresar un arreglo vacío)
    public function permisos()
    {
        $permisos = array();

        // $permisos['_test_'] = 'Permiso para pruebas';

        return $permisos;
    }

    // Regresa un arreglo con las rutas del modulo
    public function rutas()
    {

        $items['/iot/wcinfo']['POST'] = array(
            'name' => 'Actualiza datos del baño',
            'callback' => 'creaWCInfo',
            'token_required' => FALSE,
        );
/*
        $items['/rh/usuario']['GET'] = array(
            'name' => 'Obten usuario de RH',
            'callback' => 'getUsuario',
            'token_required' => TRUE,
        );
*/
        return $items;
    }

    /**
     * Define que secciones de configuracion requiere
     *
     * @return array Lista de secciones requeridas
     */
    public function requiereConfig()
    {
        $secciones = array();

        $secciones[] = 'dbaccess';

        return $secciones;
    }

    private $config = array();
    private $db = null;
    private $dbName = 'iot';

    /**
     * Carga UNA seccion de configuración
     *
     * Esta función será llamada por cada seccion que indique "requiereConfig()"
     *
     * @param string $sectionName Nombre de la sección de configuración
     * @param array $config Arreglo con la configuracion que corresponde a la seccion indicada
     *
     */
    public function cargaConfig($sectionName, $config)
    {
        $this->config[$sectionName] = $config;
    }

    	/**
	 * Inicializa la conexión a la base de datos
	 * 
	 * Usa los datos del archivo de configuración para hacer la conexión al Ring
	 */
	private function dbInit()
	{
		// Aún es no tenemos una conexión
		if( $this->db == null )
		{
			// Tenemos el nombre del archivo de configuración de dbAccess
			// print_r($this->config);
			if( isset( $this->config['dbaccess'], $this->config['dbaccess']['config'] ) )
			{
				// Inicializa DBAccess
				//print("Cargando configuracion DB: ".$this->config['dbaccess']['config']);
				$this->db = new \euroglas\dbaccess\dbaccess($this->config['dbaccess']['config']);

				if( $this->db->connect($this->dbName) === false )
				{
					print($this->db->getLastError());
				}
			}
		}
	}

    /**
     * Conecta a la Base de Datos
     */
    private function OBSOLETE_connect_db($dbKey)
    {
        $unaBD = null;

        if ($this->config && $this->config['dbaccess'])
        {
            $unaBD = new \euroglas\dbaccess\dbaccess($this->config['dbaccess']['config']);

            if( $unaBD->connect($dbKey) === false )
            {
                throw new Exception($unaBD->getLastError());
            }
        } else {
            throw new Exception("La BD no esta configurada");
        }

        return $unaBD;
    }

    function __construct()
    {
        $this->DEBUG = isset($_REQUEST['debug']);
    }

    // Convierte un arreglo a CSV
    private function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
    {
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $data, $delimiter, $enclosure, $escape_char);
        rewind($f);
        return stream_get_contents($f);
    }

    public function creaWCInfo() {

        // el campo tabla es requerido
        if( empty($_POST['tabla']) )
        {
            http_response_code(400);
            header('content-type: application/json');
            die(json_encode( $this->reportaErrorUsandoHateoas(
                400001,
                "Falta campo Tabla",
                "Es necesario el campo 'tabla' para saber donde insertar los datos",
                json_encode($_POST)
            )));
        }

        $tabla = $_POST['tabla'];
        $datos = $_POST; // Copia el arreglo
        unset($datos['tabla']); // No necesitamos este campo

        $campos = array_keys($datos);
        $camposCSV = implode(",",$campos); // Los nombres de campos deben ser 'sencillos', implode es suficiente

        $camposDeReemplazo = array_map( function($nombre) { return( ':'.$nombre); } , $campos);
        $camposDeReemplazoCSV = $this->array2csv($camposDeReemplazo);
        $valores = array_values($datos);
        $valoresCSV = $this->array2csv($valores); // Los valores podrian tener 
        
        $camposDeReemplazoConValores = array_combine($camposDeReemplazo,$valores);

        $this->dbInit();

        if( ! $this->db->queryPrepared('insertaIot') )
        {
            if($this->DEBUG) print('Preparando query:insertaIot'.PHP_EOL);
            $sql  = "INSERT INTO {$tabla} ({$camposCSV}) ";
            $sql .= "VALUES ( {$camposDeReemplazoCSV} ) ";

            $this->db->prepare($sql, 'insertaIot');
        }

        $sth = $this->db->execute('insertaIot', $camposDeReemplazoConValores );
        print_r($camposDeReemplazoConValores);

        http_response_code(201); // CREADO
        // Normalmente, se debe incluir un encabezado "Location" con la URL del "nuevo recurso"
        // como los datos IOT no son accessibles, omitimos dicho encabezado.
        // header('Location: /iot/wcinfo/{idChecada}');
        die("OK");

    }


        /**
     * Formatea el error usando estandar de HATEOAS
     * @param integer $code Codigo HTTP o Codigo de error interno
     * @param string  $userMessage Mensaje a mostrar al usuario (normalmente, con vocabulario sencillo)
     * @param string  $internalMessage Mensaje para usuarios avanzados, posiblemente con detalles técnicos
     * @param string  $moreInfoUrl URL con una explicación del error, o documentacion relacionada
     * @return string Error en un arreglo, para ser enviado al cliente usando json_encode().
    */
    private function reportaErrorUsandoHateoas($code=400, $userMessage='', $internalMessage='',$moreInfoUrl=null)
    {
        $hateoas = array(
            'links' => array(
                'self' => $_SERVER['REQUEST_URI'],
            ),
        );
        $hateoasErrors = array();
        $hateoasErrors[] = array(
            'code' => $code,
            'userMessage' => $userMessage,
            'internalMessage' => $internalMessage,
            'more info' => $moreInfoUrl,
        );

        $hateoas['errors'] = $hateoasErrors;

        // No lo codificamos con Jason aqui, para que el cliente pueda agregar cosas mas facilmente
        return($hateoas);
    }

    private $DEBUG = false;
}