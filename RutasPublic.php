<?php
include_once 'Helper/valid.php';
include_once './Model/ConnectDB.php';
include_once './Model/ConnectDBmultimedia.php';
require('./vendor/autoload.php');
$resp = new RutasPublic();
$helper = new Helper\valid();
$paramsPost = json_decode(file_get_contents('php://input'), true);
$paramsGet = $_GET;
$codigo = 500;
$connect = new connectDB();
$connectMultimedia = new connectDBmultimedia();
foreach ($paramsGet as $key =>$value){
    $funtion = $key;
}
if (isset($funtion)) {
    switch ($funtion) {
        case "Login":
            if ($helper->validParams($paramsPost,['user','pass','server']))
                $connect->validLogin($paramsPost);
            break;
        case "Lovs":
            if ($helper->validParams($paramsPost, ['action']))
                $connect->getParams($paramsPost['action'],
                    empty($paramsPost['condition']) ? '0' : $paramsPost['condition'],
                    isset($paramsPost['selects']) ? 'listas' : ' ');
            break;

        case "infoCandidato":
            if ($helper->validParams($paramsPost, ['numero', 'org']))
                $connect->getinfoCandidato($paramsPost['numero'], $paramsPost['org']);
            break;
        case "interviewChatbot":
            $connect->insertValueCandidato($paramsPost);
            break;
        case "getInfoValidCode":
            if ($helper->validParams($paramsPost, ['id_org', 'num', 'code']))
                $connect->getValidCode($paramsPost['id_org'], $paramsPost['num'], $paramsPost['code']);
            break;

        case "getInfoVacante":
            if ($helper->validParams($paramsPost, ['org', 'campania', 'requisicion', 'candidato']))
                $connect->getInfoVacante($paramsPost['org'], $paramsPost['campania'], $paramsPost['requisicion'], $paramsPost['candidato']);
            break;

        case "getCampanias":
            if ($helper->validParams($paramsPost, ['key']))
                $connect->getCampanias($paramsPost['key']);
            break;

        case "getQuestions":
            if ($helper->validParams($paramsPost, ['id_org', 'id_campain', 'id_requisicion']))
                $connect->getQuestions($paramsPost['id_org'], $paramsPost['id_campain'], $paramsPost['id_requisicion']);
            break;

        case "getDatachat":
            if ($helper->validParams($paramsPost, ['id']))
                $connectMultimedia->getDataChat($paramsPost['id']);
            break;

        case "saveArchive":
            if ($helper->validParams($paramsPost, ['valores'])) {
                $connectMultimedia->saveArchive($paramsPost['valores']);
            }
            break;

        case "getCodeInfo":
            if($helper->validParams($paramsPost, ['code']))
                $connect->getCodeInfo($paramsPost['code']);
            break;

        case "getRequisiciones":
            if ($helper->validParams($paramsPost, ['id_org', 'id_campain']))
                $connect->getRequisiciones($paramsPost['id_org'], $paramsPost['id_campain']);
            break;
//Revisar este por que hay que pasarlo a un servicio en especifico para el bot
        case "updateStatus":
            $connect->updateStatus($paramsPost['action'], $paramsPost['id']);
            break;

        case "ConfigWidjet":
            if ($helper->validParams($paramsPost, ['id']))
                $connectMultimedia->getConfigWidjet($paramsPost['id']);
            break;
        case "tipo_user_valor":
                $connect->getTipoUserValor();
            break;
        default:
            break;
    }
    if(sizeof($connect->getData())>0){
        $codigo = $connect->getCode();
        $responseBack = array('code'=>$connect->getCode(),'response' => $connect->getData());
    }
    else if(sizeof($connectMultimedia->getData())>0){
        $responseBack = array('code'=>$connectMultimedia->getCode(),'response' => $connectMultimedia->getData());
        $codigo = $connectMultimedia->getCode();
    }
    else
        $responseBack = array('code'=>$connect->getCode(),'response' => 'Error al ejecutar la acción');

}
$resp->respuesta($codigo,$responseBack);
class RutasPublic
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