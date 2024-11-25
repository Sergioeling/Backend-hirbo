<?php
include_once 'ConfigDataBase.php';
include_once 'assignDate.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Model\assignDate;

class connectDB{
    private $mysqli;
    private $code = 500;
    private $data = [];
    function __construct()
    {
        try{
            $configDB = ConfigDataBase::getConfig('');
            $this->mysqli = new mysqli(
                $configDB['host'],
                $configDB['user'],
                $configDB['password'],
                $configDB['bd'],
                $configDB['puerto']
            );
            if($this->mysqli->connect_errno){
                echo 'Hubo un error en la conexion a la BD';die;
            }
            $this->setCode(500);
        }catch (Exception $ex){
            echo 'Hubo un error en el servidor';die;
        }
    }
    function validLogin($data){
        $user = $data['user'];
        $pass = $data['pass'];
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_validar_credenciales`('$user',@p1,@p2)") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        $verify = $this->paramsReturn($this->mysqli->query("SELECT @p1 AS p_valido,@p2 AS p_tipo"));
        if ($verify['p_valido']){
            $this->__construct();
            $valores = $this->paramsReturn($dta);
            if (password_verify($pass, $valores['CONTRASENIA'])){
                $this->setCode(200);
                unset($valores['CONTRASENIA']);
                $token = array('token'=>$this->generarToken($user,$data['server']));
                $perm = ['S/A'];
                $pln = [0];
                $pln = array('PLAN'=>$valores['PLAN']);
                $arr = $this->getPermissionsOrg($valores['ID']);
                $perm = array('PERMISSIONS'=>implode(",", $arr));
                if ($valores['STATUS'] == '1')
                    $this->setData(array_merge($verify,$valores,$token,$perm,$pln));
                else
                    $this->setData(['inactivo']);
            }
            else
                $this->setCode(401);
        }
        $this->closeSql();
    }
    function closeSql(): void
    {
        if ($this->mysqli instanceof mysqli) {
            mysqli_close($this->mysqli);
        }
    }
    function getOrganization($id){
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_GetOrganizationInfo`('$id')") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $this->setData($this->paramsReturn($dta));
        }
        $this->closeSql();
    }
    function  getParams($tbl,$condition,$type)
    {
        if ($condition == '0')
            $condition = intval($condition);

        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_GetValues`('$tbl','$condition','$type')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];

            unset($vlr['CONTRASENIA']);
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function  getCampanias($key)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_getCampanias`('$key')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData(['Campanias'=>$vlr]);
        }
        $this->closeSql();
    }
    function  getRequisiciones($idOrg,$idCampain)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_getRequisiciones`('$idOrg','$idCampain')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData(['Requisiciones'=>$vlr]);
        }
        $this->closeSql();
    }

    function getTipoUserValor() {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "SELECT NOMBRE_TIPO_USER FROM `tbl_tipo_user` WHERE NOMBRE_TIPO_USER <> 'ROOT' AND NOMBRE_TIPO_USER <> 'ORGANIZACION'") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        
        if (mysqli_num_rows($dta) > 0) {
            $resultArray = [];
            while ($row = mysqli_fetch_assoc($dta)) {
                $resultArray[] = $row['NOMBRE_TIPO_USER']; 
            }
            $this->setCode(200);
            $this->setData($resultArray);
        } else {
            $this->setCode(404);
            $this->setData([]);  
        }
        $this->closeSql();
    }

    function  getDataInterview($idOrg)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_GetInfoInterview`('$idOrg')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function getPermissionsOrg($idPermissions,$setdata = 0)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_showpermisions`('$idPermissions')") or die(mysqli_error($this->mysqli));
        if (mysqli_num_rows($dta) > 0) {
            $vlr = [];
            while ($row = mysqli_fetch_assoc($dta)) {
                $vlr[] = $row['NOMBRE_PERMISO'];
            }
            if (count($vlr) == 1) {
                return $vlr[0];
            }

            if ($setdata == 1){
                $this->setCode(200);
                $this->setData($vlr);
                $this->closeSql();
            }
            else
                return $vlr;
        }

        return [];
    }

    function  getDataChangeArchive($idOrg,$idQuestion)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_GetDataArchivos`('$idOrg','$idQuestion')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            $vlr['PREGUNTA']= explode(',',$vlr['PREGUNTA']);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function deleteUrl($idUrl)
    {
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_deleteUrl`('$idUrl')") or die(mysqli_error($this->mysqli));
        if (mysqli_affected_rows($this->mysqli) > 0) {
            $this->setCode(200);
            $this->setData(['Action' => 'Deleted']);
        } else {
            $this->setCode(404);
            $this->setData(['Action' => 'No Record Found']);
        }
        $this->closeSql();
    }

    function getLeadsHorario($id) {
        $idd = intval($id);
        $stmt = mysqli_prepare($this->mysqli, "SELECT thl.ID_HORARIO, thl.FK_ID_ORGANIZACION, thl.DIA, thl.HORAS, thl.CATEGORIA, thl.ID_CALENDAR, thl.PRECIO, thl.RESPONSABLE, thl.STATUS FROM tbl_horarios_leads thl WHERE thl.FK_ID_ORGANIZACION = ?");
        mysqli_stmt_bind_param($stmt, "i", $idd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $this->setCode(200);
            $this->setData($data);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudo obtener los datos']);
        }
    }

    function deleteData($tbl,$id)
    {
        $id = intval($id);
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_deleteData`('$tbl','$id')") or die(mysqli_error($this->mysqli));
        if (mysqli_affected_rows($this->mysqli) > 0) {
            $this->setCode(200);
            $this->setData(['Action' => 'Deleted']);
        } else {
            $this->setCode(404);
            $this->setData(['Action' => 'No Record Found']);
        }
        $this->closeSql();
    }
    function getQuestionsInterview($idOrg, $idCampania, $idRequiscion)
    {
        mysqli_next_result($this->mysqli);
        $query = "CALL sp_getQuestionsInterview('$idOrg', '$idCampania', '$idRequiscion')";
        $result = mysqli_query($this->mysqli, $query) or die(mysqli_error($this->mysqli));
        if (mysqli_num_rows($result) > 0) {
            $this->setCode(200);
            $data = $this->paramsReturn($result);

            if (mysqli_num_rows($result) == 1) {
                $data = [$data];
            }
            $serie = [];
            foreach ($data as &$dt) {
                $serie = $dt['IDS'];
                unset($dt['IDS']);
            }
            unset($dt);
            $this->setData($this->order($data,explode(',',$serie)));
        }
        $this->closeSql();
    }

    function  getinfoCandidato($num,$org)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL sp_validCandidato('$num','$org')");
        if ($dta === false) {
            $this->setCode(500);
            $this->setData(['error' => mysqli_error($this->mysqli)]);
            return;
        }
        if ($dta instanceof mysqli_result && mysqli_num_rows($dta) > 0) {
            $vlr = $this->paramsReturn($dta);
        } else {
            $vlr = ['null'];
        }
        $vlr = [$vlr];
        $this->setCode(200);
        $this->setData($vlr);
        if ($dta instanceof mysqli_result) {
        mysqli_free_result($dta);
        }
        $this->closeSql();
    }
    function updateStatus($tbl,$id){
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_UpdateStatus`('$tbl', '$id')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli)>0){
            $this->setCode(200);
            $this->setData(['Action'=>'Actualizado']);
        }
    }
    function insertNumWhatsapp($idOrg, $num) {
        mysqli_next_result($this->mysqli);
        $resultado = mysqli_query($this->mysqli, "CALL sp_addPhoneNumber('$num', '$idOrg', @resultado)") or die(mysqli_error($this->mysqli));
        $resultadoQuery = mysqli_query($this->mysqli, "SELECT @resultado AS resultado");
        $row = mysqli_fetch_assoc($resultadoQuery);
        $resultadoValor = $row['resultado'];
        if ($resultadoValor) {
            $this->setCode(201);
            $this->setData(['Action' => 'Acción ejecutada correctamente']);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudo insertar (datos ya existen o condición no válida)']);
        }
    }


    function updateData($data,$setear = true){
        $valor = $this->getParamsUpdate($data['valores']);
        $tabla = $data['action'];
        $id = intval($data['id']);
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_UpdateValues`('$valor','$tabla','$id')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli)>0){
            $this->setCode(200);
            if ($setear === true) {
                $this->setData(['Action' => 'Actualizado']);
            }
        }
    }
    function ActiveAccountOracle($id){
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_ActiveAccountOracle`('$id')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli)>0){
            $this->setCode(200);
            $this->setData(['Action' => 'Acción correcta']);
        }else{
            $this->setData(['Action' => 'La cuenta no contiene la información necesaria para poder realizar está acción.']);
        }
    }
    function insertData($data) {
        $valor = $this->getParamsInsert($data['valores']);
        $tabla = $data['action'];

        $sql = 'INSERT INTO tbl_' . $tabla . ' (' . $valor['insert'] . ') VALUES (' . $valor['values'] . ')';
        mysqli_query($this->mysqli, $sql) or die(mysqli_error($this->mysqli));

        if (mysqli_affected_rows($this->mysqli) > 0) {
            $lastInsertId = mysqli_insert_id($this->mysqli);
            $this->setCode(200);
                $this->setData([
                    'Action' => 'Insertado'
                ]);

        }
    }

    function inserLeadsHorario($p_org, $p_dia, $p_horas, $p_categoria, $p_id_calendar, $p_precio, $p_responsable) {
        mysqli_next_result($this->mysqli);
        $resultado = mysqli_query($this->mysqli, "CALL sp_insertHoraiosLeads ('$p_org', '$p_dia', '$p_horas', '$p_categoria', '$p_id_calendar', '$p_precio', '$p_responsable')") or die(mysqli_error($this->mysqli));
        if ($resultado) {
            $this->setCode(201);
            $this->setData(['Action' => 'Acción ejecutada correctamente']);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudo insertar (datos ya existen o condición no válida)']);
        }
    }

    function deleteCategoriaAsociada($id) {
        mysqli_next_result($this->mysqli);
        $calendar_id = mysqli_real_escape_string($this->mysqli, $id);
        $query = "DELETE FROM tbl_horarios_leads WHERE ID_CALENDAR = '$id'";
        $resultado = mysqli_query($this->mysqli, $query) or die(mysqli_error($this->mysqli));
    
        if ($resultado) {
            $rowCount = mysqli_affected_rows($this->mysqli);
    
            $this->setCode(200);
            $this->setData([
                'Action' => 'Registros eliminados correctamente',
                'RegistrosEliminados' => $rowCount
            ]);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudieron eliminar los registros']);
        }
    }

    function deleteCategoriaAsociadaLocales($id) {
        mysqli_next_result($this->mysqli);
        $calendar_id = mysqli_real_escape_string($this->mysqli, $id);
        $query = "DELETE FROM tbl_horarios_leads WHERE CATEGORIA = '$id'";
        $resultado = mysqli_query($this->mysqli, $query) or die(mysqli_error($this->mysqli));
    
        if ($resultado) {
            $rowCount = mysqli_affected_rows($this->mysqli);
    
            $this->setCode(200);
            $this->setData([
                'Action' => 'Registros eliminados correctamente',
                'RegistrosEliminados' => $rowCount
            ]);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudieron eliminar los registros']);
        }
    }

    function getCategoriasByCalendarId($id) {
        mysqli_next_result($this->mysqli);
        
        $calendar_id = mysqli_real_escape_string($this->mysqli, $id);
        $query = "SELECT * FROM tbl_horarios_leads WHERE ID_CALENDAR = '$id'";
        
        $resultado = mysqli_query($this->mysqli, $query);
        
        if ($resultado) {
            $categorias = [];
            while ($row = mysqli_fetch_assoc($resultado)) {
                $categorias[] = $row;
            }
            
            $this->setCode(200);
            $this->setData($categorias);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudieron obtener las categorías']);
        }
    }

    function getCategoriasByCalendarIdLocal($id) {
        mysqli_next_result($this->mysqli);
        
        $calendar_id = mysqli_real_escape_string($this->mysqli, $id);
        $query = "SELECT * FROM tbl_horarios_leads WHERE CATEGORIA = '$id'";
        
        $resultado = mysqli_query($this->mysqli, $query);
        
        if ($resultado) {
            $categorias = [];
            while ($row = mysqli_fetch_assoc($resultado)) {
                $categorias[] = $row;
            }
            
            $this->setCode(200);
            $this->setData($categorias);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudieron obtener las categorías']);
        }
    }

    function GetDataConfigurationCorreo($estado){
        $dta = mysqli_query($this->mysqli, "CALL sp_GetDataConfigurationCorreo()") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $this->setData($this->paramsReturn($dta));
        }
        $this->closeSql();
    }
    function AddConfigurationCorreo($data){
        $name = $data['TEMPLATE_NAME'];
        $template = $data['TEMPLATE'];
        $components = $data['ADDED_COMPONENTS'];
        $design = $data['FORM_DESIGN'];
        $estado = $data['ESTADO'];
        $updatedby = $data['LAST_UPDATED_BY'];
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL sp_AddConfigurationCorreo('$name', '$template', '$components', '$design', '$estado', '$updatedby')") or die(mysqli_error($this->mysqli));
        if (mysqli_affected_rows($this->mysqli)>0) {
            $this->setCode(201);
            $this->setData(['Action'=>'Accion ejecutada correctamente']);
        } else {
            $this->setData(['Action'=>'No se pudo insertar (datos ya existen o condición no válida)']);
        }
    }
    function UpdateConfigurationCorreo($data){
        $id = $data['ID_CORREO'];
        $name = $data['TEMPLATE_NAME'];
        $template = $data['TEMPLATE'];
        $components = $data['ADDED_COMPONENTS'];
        $design = $data['FORM_DESIGN'];
        $estado = $data['ESTADO'];
        $updatedby = $data['LAST_UPDATED_BY'];
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL sp_UpdateConfigurationCorreo($id,'$name', '$template', '$components', '$design', '$estado', '$updatedby')") or die(mysqli_error($this->mysqli));
        if (mysqli_affected_rows($this->mysqli)>0) {
            $this->setCode(201);
            $this->setData(['Action'=>'Accion ejecutada correctamente']);
        } else {
            $this->setData(['Action'=>'No se pudo insertar (datos ya existen o condición no válida)']);
        }
    }
    function getClientesOrg($fk_id_org){
        $id = intval($fk_id_org);
        $stmt = mysqli_prepare($this->mysqli, "SELECT ID_CLIENTE,FK_ID_ORGANIZACION,NOMBRE,DESCRIPCION,TELEFONO,CORREO,STATUS FROM tbl_clientes WHERE FK_ID_ORGANIZACION = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $this->paramsReturnBlim($stmt);
    }
    //mover
    function getProspectosOrg($fk_id_org) {
        $stmt = mysqli_prepare($this->mysqli, "SELECT ID_PROSPECTO, FK_ID_ORGANIZACION, NOMBRE, DESCRIPCION, TELEFONO, CORREO, STATUS FROM tbl_prospectos WHERE FK_ID_ORGANIZACION = ?");
        mysqli_stmt_bind_param($stmt, "i", $fk_id_org);
        mysqli_stmt_execute($stmt);
        
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($resultado) {
            $prospectos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
            
            $this->setCode(200);
            $this->setData($prospectos);
        } else {
            $this->setCode(400);
            $this->setData(['Action' => 'No se pudieron obtener los prospectos']);
        }
        
        mysqli_stmt_close($stmt);
    }

    function saveOrganization($data) {
        $valor = $this->getParamsInsert($data);
        $carpetaPath = '../' . $data['CARPETA'];

        if (is_dir($carpetaPath)) {
            $this->setCode(400);
            $this->setData(['message' => 'La carpeta ya existe. No se puede insertar.']);
            return;
        }

        if (!mkdir($carpetaPath, 0777, true)) {
            $this->setCode(500);
            $this->setData(['message' => 'Error al crear la carpeta. No se puede insertar.']);
            return;
        }

        $sourceFile = '../widget/index.html';
        $destinationFile = $carpetaPath . '/index.html';

        if (!copy($sourceFile, $destinationFile)) {
            $this->setCode(500);
            $this->setData(['message' => 'Error al copiar el archivo index.html.']);
            return;
        }

        $sql = 'INSERT INTO tbl_organizacion (' . $valor['insert'] . ') VALUES (' . $valor['values'] . ')';
        if (mysqli_query($this->mysqli, $sql)) {
            if (mysqli_affected_rows($this->mysqli) > 0) {
                $lastInsertId = mysqli_insert_id($this->mysqli);
                $this->setCode(200);
                $this->setData([
                    'Action' => 'Insertado',
                    'lastInsertId' => $lastInsertId
                ]);
            }
        } else {
            $this->setCode(500);
            $this->setData(['message' => 'Error al ejecutar la consulta: ' . mysqli_error($this->mysqli)]);
        }
    }
    function insertValueCandidato($data){
        $update = true;
        if (intval($data['valores']['ID_CANDIDATO']) == 0) {
            unset($data['valores']['ID_CANDIDATO']);
            $update = false;
        }

        $valor = $this->getParamsInsert($data['valores']);
        $tabla = $data['action'];

        if (!$update) {
            $sql = 'INSERT INTO tbl_' . $tabla . ' (' . $valor['insert'] . ') VALUES (' . $valor['values'] . ')';
            $result = mysqli_query($this->mysqli, $sql);
            if (!$result) {
                die('Error en la consulta de inserción: ' . mysqli_error($this->mysqli));
            }
            $lastInsertId = mysqli_insert_id($this->mysqli);
        } else {
            $item = array('action' => 'candidatos', 'valores' => $data['valores'], 'id' => $data['valores']['ID_CANDIDATO']);
            $this->updateData($item, false);
            $lastInsertId = intval($data['valores']['ID_CANDIDATO']);
        }

        $dt = $this->generateFecha($data['valores']['FK_ID_CAMPANIA'], $data['valores']['FK_ID_ORGANIZACION'], $lastInsertId, $data['valores']['FK_ID_REQUISICION']);

        $this->setCode(200);

        if (!$update) {
            if (intval($lastInsertId)>0 ){
                $this->setData([
                    'Action' => 'Insertado',
                    'Date' => $dt
                ]);
            }
        } else {
            $this->setData([
                'Action' => 'Actualizado',
                'Date' => $dt
            ]);
        }
    }

    function sendAviso($params){
            $response = $this->sendEmailGlobal($params);
            if( $response){
                $this->setCode(200);
                $this->setData(['Action'=>'Enviado']);
                return true;
            }
            return false;

    }
    function sendEmailGlobal($params){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://hirbo.arvispace.com/EMAILS/structSendEmail.php');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    //5:07 pm ya jala esta version token con id 
    public function insertGoogleEvent($params) {
        try {
            // Verificar que existe el ID_ORGANIZACION
            if (!isset($params['idOrganizacion'])) {
                $this->code = 400;
                $this->data = array('error' => 'ID_ORGANIZACION es requerido');
                return $this->data;
            }
    
            // Verificar que existe eventData
            if (!isset($params['eventData'])) {
                $this->code = 400;
                $this->data = array('error' => 'eventData es requerido');
                return $this->data;
            }
    
            // Obtener el calendarId del request o usar 'primary' como valor predeterminado
            $calendarId = isset($params['calendarId']) ? $params['calendarId'] : 'primary';
            
            // Codificar el calendarId para la URL
            $encodedCalendarId = urlencode($calendarId);
    
            // Obtener el token de la base de datos
            mysqli_next_result($this->mysqli);
            $query = "SELECT TOKEN_GOOGLE 
                      FROM tbl_organizacion 
                      WHERE ID_ORGANIZACION = ?";
                      
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("i", $params['idOrganizacion']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $token = $row['TOKEN_GOOGLE'];
            } else {
                $this->code = 400;
                $this->data = array('error' => 'No se encontró token de Google para esta organización');
                return $this->data;
            }
    
            // Verificar que el token no sea nulo
            if (!$token) {
                $this->code = 400;
                $this->data = array('error' => 'Token de Google no válido para esta organización');
                return $this->data;
            }
    
            $curl = curl_init();
            // Usar el calendarId en la URL
            curl_setopt($curl, CURLOPT_URL, "https://www.googleapis.com/calendar/v3/calendars/{$encodedCalendarId}/events");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params['eventData']));
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if (curl_errno($curl)) {
                $this->code = 500;
                $this->data = array('error' => curl_error($curl));
            } else {
                $responseData = json_decode($response, true);
                if ($httpCode >= 200 && $httpCode < 300) {
                    $this->code = 200;
                    $this->data = array('success' => true, 'data' => $responseData);
                } else {
                    $this->code = $httpCode;
                    $this->data = array(
                        'success' => false, 
                        'error' => $responseData['error']['message'] ?? 'Error al insertar evento',
                        'details' => $responseData
                    );
                }
            }
            
            curl_close($curl);
            
        } catch (Exception $e) {
            $this->code = 500;
            $this->data = array('error' => $e->getMessage());
        }
        
        return $this->data;
    }
    
    

    function sendWhatsappUser($id,$datas){
        $sql= mysqli_query($this->mysqli, "SELECT tb.URLAPI 'API' FROM tbl_telefonosChatBot tb WHERE tb.FK_ID_ORGANIZACION = '$id'") or die(mysqli_error($this->mysqli));
        $api = $this->paramsReturn($sql);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api['API']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datas));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $this->setCode(200);
        $this->setData(["message"=>$response]);
    }
    function getPreguntas($preguntas,$id_org)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL sp_getPreguntas('$preguntas','$id_org')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($this->order($vlr,explode(',',$preguntas)));
        }
        $this->closeSql();
    }


    function generateQrUser($id) {

        $sql = mysqli_query($this->mysqli, "
        SELECT EXISTS (
            SELECT 1 FROM tbl_organizacion tor WHERE tor.ID_ORGANIZACION = '$id'
        ) AS org_exists,
        EXISTS (
            SELECT 1 FROM tbl_usuario tu WHERE tu.ID_USUARIO = '$id'
        ) AS user_exists
    ") or die(mysqli_error($this->mysqli));

        if ($sql && mysqli_num_rows($sql) > 0) {
            $result = mysqli_fetch_assoc($sql);

            if ($result['org_exists'] || $result['user_exists']) {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'https://hirbo.arvispace.com/ChatBotNodeJS/qr/' . $id);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                $response = curl_exec($curl);
                curl_close($curl);

                $responseData = json_decode($response, true);

                $this->setCode(200);
                $this->setData($responseData);

                //var_dump($responseData);
            } else {
                $this->setCode(500);
                $this->setData(["message" => 'Usuario no válido para generar QR']);
            }
        } else {
            $this->setCode(500);
            $this->setData(["message" => 'Error en la consulta a la base de datos']);
        }
    }
    function validSession($id) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://hirbo.arvispace.com/ChatBotNodeJS/validSession/'.$id);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['userId' => $id]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, true);
        $this->setCode(200);
        $this->setData($responseData);
        if (!$response) {
            $this->setCode(500);
            $this->setData(["message" => 'Usuario no válido para generar QR']);
        }
    }
    function sendWhatsappSession($userId, $to, $message,$media) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://7pz70xqx-3000.usw3.devtunnels.ms/message');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            'userId' => $userId,
            'to' => $to,
            'message' => $message
        ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, true);

        if (isset($responseData['code']) && $responseData['code'] == 200) {
            $this->setCode(200);
            $this->setData($responseData);
        } else {
            $this->setCode(500);
            $this->setData(["message" => 'Error al mandar mensaje']);
        }
    }


    function getMensajesClientes($id_org)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "SELECT ID, TITULO, MENSAJE, MULTIMEDIA FROM `tbl_mensajes_clientes` WHERE FK_ID_ORGANIZACION = $id_org") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);

        if(mysqli_num_rows($dta) > 0)
        {
            $this->setCode(200);
            $this->setData($this->paramsReturn($dta));
        }
        $this->closeSql();
    }

    function dropMensajesClientes($id)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "DELETE FROM `tbl_mensajes_clientes` WHERE `tbl_mensajes_clientes`.`ID` = $id") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);

        if (mysqli_affected_rows($this->mysqli) > 0)
        {
            $this->setCode(200);
            $this->setData(['message' => 'Mensaje eliminado correctamente.']);
        }
        else
        {
            $this->setCode(404);
            $this->setData(['message' => 'No se encontró el mensaje o no se pudo eliminar.']);
        }
        $this->closeSql();
    }


    function insertMensajesClientes($mensaje, $multimedia, $id_org, $titulo)
    {
        $mensaje = mysqli_real_escape_string($this->mysqli, $mensaje);
        $multimedia = mysqli_real_escape_string($this->mysqli, $multimedia);
        $id_org = mysqli_real_escape_string($this->mysqli, $id_org);
        $titulo = mysqli_real_escape_string($this->mysqli, $titulo);

        $query = "INSERT INTO tbl_mensajes_clientes (MENSAJE, MULTIMEDIA, FK_ID_ORGANIZACION, TITULO)
                VALUES ('$mensaje', '$multimedia', '$id_org', '$titulo')";

        if (mysqli_query($this->mysqli, $query))
        {
            $last_id = mysqli_insert_id($this->mysqli);

            $this->setCode(200);
            $this->setData
            ([
                'id' => $last_id,
                'mensaje' => $mensaje,
                'multimedia' => $multimedia,
                'id_org' => $id_org,
                'titulo' => $titulo
            ]);
        }
        else
        {
            die('Error en la consulta: ' . mysqli_error($this->mysqli));
        }
        $this->closeSql();
    }

    function updateMensajes($id, $mensaje, $multimedia, $titulo)
    {
        $id = mysqli_real_escape_string($this->mysqli, $id);
        $mensaje = mysqli_real_escape_string($this->mysqli, $mensaje);
        $multimedia = mysqli_real_escape_string($this->mysqli, $multimedia);
        $titulo = mysqli_real_escape_string($this->mysqli, $titulo);

        $query = "UPDATE tbl_mensajes_clientes SET MENSAJE = '$mensaje', MULTIMEDIA = '$multimedia', TITULO = '$titulo'
                WHERE ID = $id";

        if (mysqli_query($this->mysqli, $query))
        {
            $this->setCode(200);
            $this->setData(['message' => 'Mensaje actualizado correctamente.']);
        }
        else
        {
            $this->setCode(500);
            $this->setData(['error' => 'Error en la consulta: ' . mysqli_error($this->mysqli)]);
        }
        $this->closeSql();
    }
    function tipoEstado($data){
        $user = $data['user'];
        $id = $data['idOrg'];
        $idTipo = $data['idTipo'];
        $nombre = $data['nombre'];
        $descripcion = $data['descripcion'];
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_tipoEstado`('$id','$idTipo','$nombre','$descripcion','$user')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli)>0){
            $this->setCode(200);
            $this->setData(['Action'=>'Accion realizada correctamente']);
        }
    }
    function addQuestion($question,$idTipo,$tabla,$answer,$idOrg,$idQuestion,$priority,$correct,$multi){
        mysqli_next_result($this->mysqli);
        if ($tabla == 1)
            mysqli_query($this->mysqli, "CALL `sp_AddQuestion`('$question','$idTipo','$answer','$idOrg','$idQuestion','$priority','$correct','$multi')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli)>0){
            $this->setCode(200);
            $this->setData(['Action'=>'Insertado']);
        }
    }

    function dataGraphics($IDORG)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_RequisicionesPorCampania`('$IDORG')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }

    function getValidCode($IDORG,$num,$code)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_ValidArchiveCandidato`('$IDORG','$num','$code')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function getDataCandidatos($IDORG)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_DataCandidatos`('$IDORG')") or die(mysqli_error($this->mysqli));

        if (mysqli_num_rows($dta) > 0) {
            $this->setCode(200);
            $vlr = $this->paramsReturn($dta);

            if (mysqli_num_rows($dta) == 1) {
                $vlr = [$vlr];
            }

            foreach ($vlr as &$item) {
                if ($item['DOCUMENTO'] !== 'N/A') {
                    $documentoUrl = 'https://hirbo.arvispace.com/Back/services' . $item['DOCUMENTO'];
                    if ($this->isFileAccessible($documentoUrl)) {
                        $item['DOCUMENTO'] = $documentoUrl;
                    } else {
                        $item['DOCUMENTO'] = 'N/A';
                    }
                }
            }
            $this->setData($vlr);
        }

        $this->closeSql();
    }

    private function isFileAccessible($url)
    {
        $headers = get_headers($url, 1);
        return strpos($headers[0], '200') !== false;
    }


    function getListDataColaboradores($id_TIPO,$idOrg)
    {
        mysqli_next_result($this->mysqli);
        $result = mysqli_query($this->mysqli, "CALL 	`sp_getDataColaboradores`('$id_TIPO','$idOrg')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($result)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($result);
            if(mysqli_num_rows($result)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function getHCMCandidatos($IDORG)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_GetCredentialsOracle`('$IDORG')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){

            $vlr =$this->paramsReturn($dta);
            $usuario = $vlr['USUARIO'];
            $contrasenia = $vlr['CONTRASENIA_CLOUD'];
            $credentials = base64_encode("$usuario:$contrasenia");
            $url = $vlr['RUTA'];
            $method = $vlr['METODO'];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . $credentials
            ]);
            $response = json_decode(curl_exec($curl), true);
            if ($response === false) {
                echo 'Error: ' . curl_error($curl);
                var_dump('Credenciales incorrectas');
            } else {
                $this->setData($response['items']);
            }
            curl_close($curl);
            $this->setCode(200);
        }
        $this->closeSql();
    }

    function getInfoVacante($org,$campania,$req,$cand)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL `sp_getInfoMessageVacante`('$org','$campania','$req','$cand')") or die(mysqli_error($this->mysqli));
        if(mysqli_num_rows($dta)>0){
            $this->setCode(200);
            $vlr =$this->paramsReturn($dta);
            if(mysqli_num_rows($dta)== 1)
                $vlr = [$vlr];
            $this->setData($vlr);
        }
        $this->closeSql();
    }
    function generateFecha($idCampania,$idOrg,$idCandidato,$area)
    {
        mysqli_next_result($this->mysqli);
        $Automa = mysqli_query($this->mysqli, "SELECT tp.AUTOMATIZADA FROM tbl_entrevistas_programadas tp WHERE tp.FK_ID_ORGANIZACION = $idOrg LIMIT 1") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        $horarios = mysqli_query($this->mysqli, "SELECT th.DIA, th.HORAS FROM tbl_horarios_prueba th WHERE th.FK_ID_ORGANIZACION = $idOrg AND th.FK_ID_CAMPANIA = $idCampania AND th.STATUS = 1 ORDER BY FIELD(th.DIA, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')") or die(mysqli_error($this->mysqli));
        mysqli_next_result($this->mysqli);
        $ocupados = mysqli_query($this->mysqli, "SELECT tpe.DIA, tpe.HORARIOS FROM tbl_programacion_entrevistas tpe WHERE tpe.FK_ID_ORGANIZACION = $idOrg AND tpe.FK_ID_CAMPANIA = $idCampania AND tpe.STATUS = 1") or die(mysqli_error($this->mysqli));
        $fechas = mysqli_query($this->mysqli, "SELECT tc.FECHA_INICIO,tc.FECHA_FIN FROM tbl_campania tc WHERE tc.ID_CAMPANIA = $idCampania") or die(mysqli_error($this->mysqli));
        $automatiz =$this->paramsReturn($Automa);
        $hrs =$this->paramsReturn($horarios);
        $ocp =$this->paramsReturn($ocupados);
        $fc =$this->paramsReturn($fechas);
        $date1 = DateTime::createFromFormat('Y/m/d', $fc['FECHA_INICIO']);
        $date2 = new DateTime();
        $fechainicio = $date2;
        if ($date1>$date2)
            $fechainicio = $date1;

        if ($automatiz['AUTOMATIZADA'] == '1') {
            $fechaInicio = $fechainicio;
            $fechaFin = $fc['FECHA_FIN'];
            $primerDiaEspecifico = assignDate::validatefechaAndHora($fechaInicio,$fechaFin,$hrs,$ocp);
            if ($primerDiaEspecifico) {
                if ($primerDiaEspecifico != 'No hay automatizacion' && is_array($primerDiaEspecifico)){
                    $fech = $primerDiaEspecifico[1];
                    $hor = $primerDiaEspecifico[2];
                    mysqli_next_result($this->mysqli);
                    $fech = mysqli_real_escape_string($this->mysqli, $fech);
                    $hor = mysqli_real_escape_string($this->mysqli, $hor);
                    $fech = "'$fech'";
                    $hor = "'$hor'";
                    mysqli_query($this->mysqli, "INSERT INTO `tbl_programacion_entrevistas`(`FK_ID_ORGANIZACION`, `ID_RECLUTADOR`, `FK_ID_CAMPANIA`, `FK_ID_CANDIDATO`, `AREA`, `DIA`, `HORARIOS`, `STATUS`) VALUES ($idOrg,(SELECT tc.FK_ID_RECLUTADOR FROM tbl_campania tc WHERE tc.ID_CAMPANIA = $idCampania AND tc.FK_ID_ORGANIZACION = $idOrg),$idCampania,$idCandidato,$area,$fech,$hor,'1')") or die(mysqli_error($this->mysqli));
                    $this->closeSql();
                    return [$primerDiaEspecifico[0]];
                }else{
                    $this->closeSql();
                    return [$primerDiaEspecifico];
                }
            }
        }
        return ['No hay automatizacion'];
    }
    function getQuestions($idOrg, $idCampain, $idRequisicion)
    {
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "CALL sp_getQuestions('$idOrg', '$idCampain', '$idRequisicion')") or die(mysqli_error($this->mysqli));

        $preguntas = [];
        $respuestas = [];
        if ($dta) {
            $preguntas = $this->paramsReturn($dta);

            if (mysqli_more_results($this->mysqli)) {
                mysqli_next_result($this->mysqli);
                $dta = mysqli_store_result($this->mysqli);

                if ($dta) {
                    $respuestas = $this->paramsReturn($dta);
                }
            }
        }
        $serie = '';
        foreach ($preguntas as &$pregunta) {
            $serie = $pregunta['serie'];
            unset($pregunta['serie']);
            $respuestasFiltradas = array_filter($respuestas, function ($respuesta) use ($pregunta) {
                return $respuesta['ID_PREGUNTA'] == $pregunta['ID_PREGUNTA'];
            });
            $pregunta['RESPUESTAS'] = array_map(function ($respuesta) {
                $tipoRespuesta = strtolower($respuesta['RESPUESTA']);
                if ($tipoRespuesta != 'abierta' && $tipoRespuesta != 'multimedia') {
                    return explode(',', $respuesta['RESPUESTA']);
                }else{
                    return [$respuesta['RESPUESTA']];
                }
            }, $respuestasFiltradas);
            $pregunta['RESPUESTAS'] = array_merge(...$pregunta['RESPUESTAS']);
        }

        $this->setCode(200);
        $this->closeSql();
        $this->setData(['Respuestas' => $this->order($preguntas,explode(',',$serie))]);
    }
    function order($preguntas,$orden){
        $order = $orden;
        usort($preguntas, function($a, $b) use ($order) {
            $posA = array_search($a["ID_PREGUNTA"], $order);
            $posB = array_search($b["ID_PREGUNTA"], $order);

            if ($posA !== false && $posB !== false) {
                return $posA - $posB;
            }

            if ($posA !== false) return -1;
            if ($posB !== false) return 1;

            return 0;
        });
        return $preguntas;
    }

    function getParamsUpdate($data){
        $sql = '';
        $comas = "\ ".'\'';
        $com =str_replace(" ", "", $comas);
        foreach ($data as $key => $val){
            if ($sql =='')
                $sql = ' SET '.$key.' ='.$com.$val.$com;
            else
                $sql .= ', '.$key.' ='.$com.$val.$com ;
        }
        return $sql;
    }
    function encrypt($item){
        $options = ['cost' => 15];
        return password_hash($item, PASSWORD_BCRYPT, $options);
    }
    function getParamsInsert($data){
        $insert = '';
        $values = '';
        $com = '`';
        $coma = '\'';
        foreach ($data as $key => $val){
            if ($key == 'CONTRASENIA')
                $val = $this->encrypt($val);

            if ($insert == ''){
                $insert .=$com.$key.$com;
                $values .= $coma.$val.$coma;
            }else{
                $insert .= ', '.$com.$key.$com;
                $values .= ', '.$coma.$val.$coma;
            }
        }
        return ['insert' =>$insert,'values'=>$values];
    }

    function ActivateApprovers($id){
        mysqli_next_result($this->mysqli);
        mysqli_query($this->mysqli, "CALL `sp_ActivateApprovers`('$id')") or die(mysqli_error($this->mysqli));
        if(mysqli_affected_rows($this->mysqli) > 0){
            $this->setCode(200);
            $this->setData(['Action' => 'Acción correcta']);
        }else{
            $this->setData(['Action' => 'La cuenta no contiene la información necesaria para realizar esta acción']);
        }
    }
    function getCodeInfo($code){
        mysqli_next_result($this->mysqli);
        $stmt = mysqli_prepare($this->mysqli, "CALL sp_getCodeInfo(?)");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($result && mysqli_num_rows($result) > 0 ){
            $data = mysqli_fetch_assoc($result);
            $this->setCode(200);
            $this->setData($data);
        }else {
            $this->setCode(404);
            $this->setData(['message' => 'Error al obtener el código']);
        }

        mysqli_stmt_close($stmt);
    }
    
    function generarToken($user,$server){
        $key = 'HirboSecurity';
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify('+900 minutes')->getTimestamp();
        $serverName = $server;
        $username = $user;

        $data = [
            'iat' => $issuedAt->getTimestamp(),
            'iss' => $serverName,
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire,
            'username' => $username,
        ];
        return JWT::encode($data, $key, 'HS512');
    }
    public function verificarToken() {
        $headers = getallheaders();
        $tkn = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

        $response = [
            'code' => 401,
            'message' => 'Token no proporcionado.'
        ];
        $this->setCode(401);

        if (!$tkn) {
            return $response;
        }

        $key = 'HirboSecurity';

        try {
            $token = JWT::decode($tkn, new Key($key, 'HS512'));
        } catch (Exception $e) {
            $response['message'] = 'Token inválido.';
            return $response;
        }

        $now = new DateTimeImmutable();
        $serverNameProduccion = 'http://localhost:4200';
        $serverNamelocal = 'https://www.hirbo.mx';

        if ($token->iss !== $serverNameProduccion && $token->iss !== $serverNamelocal) {
            $response['message'] = 'El dominio utilizado no es válido.';
            return $response;
        }

        if (isset($token->nbf) && $token->nbf > $now->getTimestamp()) {
            $response['message'] = 'El token no es válido antes de la fecha y hora especificadas.';
            return $response;
        }

        if ($token->exp < $now->getTimestamp()) {
            $response['message'] = 'La sesión ha expirado.';
            return $response;
        }
        $response['code'] = 200;
        $response['message'] = 'La sesión es La sesión es válida..';
        $response['data'] = $this->tiempoRestanteToken($tkn);
        return $response;
    }


    public function tiempoRestanteToken($tkn) {
        $key = 'HirboSecurity';

        try {
            $token = JWT::decode($tkn, new Key($key, 'HS512'));
        } catch (Exception $e) {
            return ['error' => 'Token inválido.'];
        }

        $now = new DateTimeImmutable();
        if (isset($token->exp)) {
            $expireTime = (new DateTimeImmutable())->setTimestamp($token->exp);
            $remainingTime = $expireTime->getTimestamp() - $now->getTimestamp();

            if ($remainingTime > 0) {
                // Convertir a minutos y segundos
                $minutes = floor($remainingTime / 60);
                $seconds = $remainingTime % 60;

                return [
                    'mensaje' => 'La sesión es válida.',
                    'tiempo_restante' => [
                        'total_segundos' => $remainingTime, // Tiempo restante en segundos
                        'minutos' => $minutes,
                        'segundos' => $seconds
                    ]
                ];
            } else {
                return ['mensaje' => 'La sesión ha expirado.'];
            }
        } else {
            return ['mensaje' => 'No se pudo determinar el tiempo restante.'];
        }
    }

    function obtenerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    function paramsReturn($data) {
        $registros_retorno = array();
        while ($registro = $data->fetch_assoc()) {
            $registros_retorno[] = $registro;
        }
        if (empty($registros_retorno)) {
            return null;
        }
        return count($registros_retorno) > 1 ? $registros_retorno : $registros_retorno[0];
    }

    function getDataProfile($id, $type) {
        mysqli_next_result($this->mysqli);
        $query = "CALL sp_dataProfile('$id', '$type')";
        $result = mysqli_query($this->mysqli, $query) or die(mysqli_error($this->mysqli));
        
        if (mysqli_num_rows($result) > 0) {
            $this->setCode(200);
            $data = $this->paramsReturn($result);
    
            if (mysqli_num_rows($result) == 1) {
                $data = [$data];
            }
            
            $this->setData($data);
        }
        
        $this->closeSql();
    }

    function updateDataProfile($id, $type, $data) {
        mysqli_next_result($this->mysqli);
        
        $nombre = $data['nombre'] ?? '';
        $ap_paterno = $data['ap_paterno'] ?? '';
        $ap_materno = $data['ap_materno'] ?? '';
        $telefono = $data['telefono'] ?? '';
        $email = $data['email'] ?? '';
        $codigo_postal = $data['codigo_postal'] ?? '';
        $descripcion = $data ['descripcion'] ?? '';
        
        $query = "CALL sp_updateProfile('$id', '$type', '$nombre', '$ap_paterno', '$ap_materno', '$telefono', '$email', '$codigo_postal', '$descripcion')";
        
        $result = mysqli_query($this->mysqli, $query) or die(mysqli_error($this->mysqli));
        
        if (mysqli_num_rows($result) > 0) {
            $this->setCode(200);
            $data = $this->paramsReturn($result);
            $this->setData($data);
        } else {
            $this->setCode(400);
            $this->setData(['message' => 'No se pudo actualizar el perfil']);
        }
        
        $this->closeSql();
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }



}