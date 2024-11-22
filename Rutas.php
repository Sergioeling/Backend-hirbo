<?php
include_once './Model/ConnectDB.php';
include_once './Model/ConnectDBmultimedia.php';
include_once 'Helper/valid.php';
require('./vendor/autoload.php');
$paramsPost = json_decode(file_get_contents('php://input'), true);
$paramsGet = $_GET;
$connect = new connectDB();
$connectMultimedia = new ConnectDBmultimedia();
$resp = new Rutas();
$helper = new Helper\valid();
$funtion = '';
foreach ($paramsGet as $key =>$value){
    $funtion = $key;
}
$codigo = 500;
$timeResult = [];
$tknverify = $connect->verificarToken();
if ($tknverify['code'] == 401){
    $resp->respuesta(401,$tknverify);
    return;
}
if (!empty($funtion) && $connect->verificarToken()['code']== 200){
    $timeResult = $connect->verificarToken()['data'];
    switch ($funtion){
        case "verifyToken":
                var_dump($connect->verificarToken());
            break;
        case "Lovs":
            if ($helper->validParams($paramsPost,['action']))
                $connect->getParams($paramsPost['action'],
                    empty($paramsPost['condition'] )? '0':$paramsPost['condition'],
                        isset($paramsPost['selects'] )? 'listas':' ');
            break;
        case "organization":
            if ($helper->validParams($paramsPost,['id']))
                $connect->getOrganization($paramsPost['id']);
            break;
        case "updateStatus":
            $connect->updateStatus($paramsPost['action'],$paramsPost['id']);
            break;
        case "updateValues":
                $connect->updateData($paramsPost);
            break;
        case "insertValue":
                $connect->insertData($paramsPost);
            break;
        case "addQuestion":
            if ($helper->validParams($paramsPost,['question','tipo','answer','idOrg','tabla']))
                $connect->addQuestion($paramsPost['question'],$paramsPost['tipo'],$paramsPost['tabla'],$paramsPost['answer'],$paramsPost['idOrg'],empty($paramsPost['idQuestion'])?'0':$paramsPost['idQuestion'],$paramsPost['prioridad'],$paramsPost['correcta'],$paramsPost['multimedia']);
            break;
        case "graphics":
            if ($helper->validParams($paramsPost,['id']))
                $connect->dataGraphics($paramsPost['id']);
            break;
            //revisar esta por que no estan consumiendo las campañas en sitio web
        case "getCampanias":
            if ($helper->validParams($paramsPost,['key']))
                $connect->getCampanias($paramsPost['key']);
            break;
        case "getQuestionsInterview":
            if ($helper->validParams($paramsPost, ['id_org', 'id_campain', 'id_requisicion']))
                $connect->getQuestionsInterview($paramsPost['id_org'], $paramsPost['id_campain'], $paramsPost['id_requisicion']);
            break;
        case "getDataArchive":
            if ($helper->validParams($paramsPost, ['id_org', 'id_question']))
                $connect->getDataChangeArchive($paramsPost['id_org'], $paramsPost['id_question']);
            break;
        case "saveOrganization":
            if ($helper->validParams($paramsPost, ['SECTOR','NOMBRE_ORGANIZACION','CODIGO_POSTAL','TELEFONO','EMAIL','STATUS','CLOUD','DESCRIPCION','USUARIO','CONTRASENIA','SUSCRIPCION','CARPETA','FECHA_INICIO_PLAN','FECHA_FIN_PLAN','FK_ID_TIPO_ORGANIZACION','FK_ID_TIPO_USER']))
                $connect->saveOrganization($paramsPost);
            break;
        case "getPermissions":
            if ($helper->validParams($paramsPost, ['id']))
                $connect->getPermissionsOrg($paramsPost['id'],1);
            break;
        case "activateApproversData":
            if ($helper->validParams($paramsPost, ['id']))
                $connect->ActivateApprovers($paramsPost['id']);
            break;
        case "saveConfigWidjet":
            if ($helper->validParams($paramsPost, ['id','rows']))
                $connectMultimedia->saveConfigWidjet($paramsPost);
            break;

        case "saveFlujoLeads":
            if ($helper->validParams($paramsPost, ['flujo','idOrg']))
                $connectMultimedia->saveFileFlijo($paramsPost['flujo'],$paramsPost['idOrg']);
            break;

        case "readFlujoLeads":
            if ($helper->validParams($paramsPost, ['idOrg']))
                $connectMultimedia->readFileFlijo($paramsPost['idOrg']);
            break;

//        case "saveData":
//            if ($helper->validParams($paramsPost, ['valores']))
//                $connect->$connectMultimedia($paramsPost['valores']);
//            break;
//        case "changeDataTipoEstado":
//            if ($helper->validParams($paramsPost, ['user','idOrg','idTipo','nombre','descripcion']))
//                $connect->tipoEstado($paramsPost);
//            break;
//        case "sendAviso":
//            if ($helper->validParams($paramsPost, ['p_Email']))
//                $connect->sendAviso($paramsPost);
//            break;
        case "deleteUrl":
            if ($helper->validParams($paramsPost, ['id_url']))
                $connect->deleteUrl($paramsPost['id_url']);
            break;
        case "deleteData":
            if ($helper->validParams($paramsPost, ['id','tbl']))
                $connect->deleteData($paramsPost['tbl'],$paramsPost['id']);
            break;
            case "insertGoogleEvent":
                if ($helper->validParams($paramsPost, ['idOrganizacion', 'eventData'])) {
                    $connect->insertGoogleEvent($paramsPost);
                }
                break;
        
//        case "insertPhoneNum":
//            if ($helper->validParams($paramsPost, ['id','telefono']))
//                $connect->insertNumWhatsapp($paramsPost['id'],$paramsPost['telefono']);
//            break;
//    eliminar insetphonenumber procedure
        case "sendWhatsappUser":
            if ($helper->validParams($paramsPost, ['id','datas']))
                $connect->sendWhatsappUser($paramsPost['id'],$paramsPost['datas']);
            break;
        case "getPreguntas":
            if ($helper->validParams($paramsPost,['preguntas','id_org']))
                $connect->getPreguntas($paramsPost['preguntas'],$paramsPost['id_org']);
            break;
        case "insertMensajesClientes":
            if ($helper->validParams($paramsPost, ['mensaje', 'multimedia', 'id_org', 'titulo']))
                $connect->insertMensajesClientes($paramsPost['mensaje'], $paramsPost['multimedia'], $paramsPost['id_org'], $paramsPost['titulo']);
            break;
        case "mensajesClientes":
            if ($helper->validParams($paramsPost,['id_org']))
                $connect->getMensajesClientes($paramsPost['id_org']);
            break;
        case "dropmensajes":
            if ($helper->validParams($paramsPost,['id']))
                $connect->dropMensajesClientes($paramsPost['id']);
            break;
        case "editMensajes":
            if ($helper->validParams($paramsPost,['id', 'mensaje', 'multimedia', 'titulo']))
                $connect->updateMensajes($paramsPost['id'], $paramsPost['mensaje'], $paramsPost['multimedia'], $paramsPost['titulo']);
            break;
        case "GenerateWidjet":
            if ($helper->validParams($paramsPost, ['id']))
                $connectMultimedia->saveWidgetOrg($paramsPost['id']);
            break;
        case "getDataHCMCandidatos":
            if ($helper->validParams($paramsPost, ['id']))
                $connect->getHCMCandidatos($paramsPost['id']);
            break;
        case "activateAccountData":
            if ($helper->validParams($paramsPost, ['id']))
                $connect->ActiveAccountOracle($paramsPost['id']);
            break;
        case "getComponentes":
            if( $helper->validParams($paramsPost,array('id_TIPO','idOrg')))
                $connect->getListDataColaboradores($paramsPost['id_TIPO'],$paramsPost['idOrg']);
            break;
        case "getDataCandidatos":
            if ($helper->validParams($paramsPost, ['id_org']))
                $connect->getDataCandidatos($paramsPost['id_org']);
            break;
        case "getInfoInterview":
            if ($helper->validParams($paramsPost,['id_org']))
                $connect->getDataInterview($paramsPost['id_org']);
            break;

        case "saveEtiquetasCalendar":
            $connectMultimedia->saveEtiquetas($paramsPost['rows'],$paramsPost['id']);
            break;
        case "generateQr":
            $connect->generateQrUser($paramsPost['id']);
            break;
        case "validateQr":
            $connect->validSession($paramsPost['id']);
            break;
        case "sendWhatsapp":
            $connect->sendWhatsappSession($paramsPost['id'],$paramsPost['to'],$paramsPost['message'],$paramsPost['media']);
            break;
        case "deleteEtiquetasCalendar":
            $connectMultimedia->deleteEtiquetas($paramsPost['rows'], $paramsPost['id']);
            break;
        case "saveEventoCalendar":
            $data = ['id' => $paramsPost['id'],'rows' => $paramsPost['rows']];$connectMultimedia->saveEvento($data);
            break;
        case "getEventos":
            $connectMultimedia->getEventosCalendar($paramsPost['id']);
            break;
        case "updateEvento":
            $connectMultimedia->updateEvento($paramsPost);
            break;
        case "getEtiquetasCalendar":
            $connectMultimedia->getEtiquetasCalendar($paramsPost['id']);
            break;
        case "deleteEvento":
            $connectMultimedia->deleteEvento($paramsPost);
            break;
            //10:37 am
            case "inserLeadsHorario":
                if($helper->validParams($paramsPost, ['FK_ID_ORGANIZACION', 'DIA', 'HORAS', 'CATEGORIA', 'ID_CALENDAR', 'PRECIO','RESPONSABLE']))
                $connect->inserLeadsHorario(
                        $paramsPost['FK_ID_ORGANIZACION'],
                        $paramsPost['DIA'],
                        $paramsPost['HORAS'],
                        $paramsPost['CATEGORIA'],
                        $paramsPost['ID_CALENDAR'],
                        $paramsPost['PRECIO'],
                        $paramsPost['RESPONSABLE']
                );
                break;
        case "getLeadsHorario":            
            if ($helper->validParams($paramsPost, ['id']))
            $connect->getLeadsHorario($paramsPost['id']);
        break;
        case "deleteCategoriaAsociada":
            if ($helper->validParams($paramsPost, ['id'])) {
                $connect->deleteCategoriaAsociada($paramsPost['id']);
            }
            break;
        case "getCategoriasByCalendarId":
            if ($helper->validParams($paramsPost, ['id'])) {
                $connect->getCategoriasByCalendarId($paramsPost['id']);
            }
            break;
        case "deleteCategoriaAsociadaLocal":
            if ($helper->validParams($paramsPost, ['id'])) {
                $connect->deleteCategoriaAsociadaLocales($paramsPost['id']);
            }
        break;
        case "getCategoriasByCalendarIdLocal":
            if ($helper->validParams($paramsPost, ['id'])) {
                $connect->getCategoriasByCalendarIdLocal($paramsPost['id']);
            }
            break;
        case "GetDataConfigurationCorreo":
            if ($helper->validParams($paramsPost,['estado']))
                $connect->GetDataConfigurationCorreo($paramsPost['estado']);
            break;
        case "AddConfigurationCorreo":
            if ($helper->validParams($paramsPost,['TEMPLATE_NAME']))
                $connect->AddConfigurationCorreo($paramsPost);
            break;
        case "UpdateConfigurationCorreo":
            if ($helper->validParams($paramsPost,['TEMPLATE_NAME']))
                $connect->UpdateConfigurationCorreo($paramsPost);
            break;
        case "getCientesOrg":
            if ($helper->validParams($paramsPost, ['id']))
                $connect->getClientesOrg($paramsPost['id']);
            break;
        case "getProspectosOrg":
                if ($helper->validParams($paramsPost, ['id']))
                    $connect->getProspectosOrg($paramsPost['id']);
            break;
        case "dataProfile":
                if ($helper->validParams($paramsPost,['id','type']))
                    $connect->getDataProfile($paramsPost['id'],$paramsPost['type']);
            break;
        case "updateProfile":
                if ($helper->validParams($paramsPost, ['id', 'type', 'data'])) {
                    $connect->updateDataProfile(
                    $paramsPost['id'], 
                    $paramsPost['type'], 
                    $paramsPost['data']
                );
            }
        break;
    }
    if(sizeof($connect->getData())>0){
        $codigo = $connect->getCode();
        $responseBack =  array_merge(array('code'=>$connect->getCode(),'response' =>$connect->getData()), $timeResult) ;
    }
    else if(sizeof($connectMultimedia->getData())>0){
        $responseBack = array_merge(array('code'=>$connectMultimedia->getCode(),'response' =>$connectMultimedia->getData()), $timeResult);
        $codigo = $connectMultimedia->getCode();
    }
    else
        $responseBack = array('code'=>$connect->getCode(),'response' => 'Error al ejecutar la acción');
}
else
    $responseBack['response'] = 'Error en la petición';

if (empty($responseBack)) {
    $responseBack = array('code' => 500, 'response' => 'Error al ejecutar la acción');
}
$resp->respuesta($codigo,$responseBack);
class Rutas
{
    function respuesta($codigoRespuesta, $respuesta)
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
            include_once 'ser_cors.php';
            exit();
        }
        include_once 'ser_cors.php';
        http_response_code($codigoRespuesta);
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}
