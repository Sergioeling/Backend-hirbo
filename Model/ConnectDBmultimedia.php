<?php

class ConnectDBmultimedia
{

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

    function saveEtiquetas($data, $id) {
        $carpeta = $this->getCarpeta($id);
        $filePath = '../' . $carpeta . '/etiquetasCalendar' . $id . '.csv';
        $file = fopen($filePath, 'w');
        if ($file === false) {
            die('Error abriendo el archivo para escritura.');
        }
        $headers = ['ID', 'Texto', 'Color', 'Checked'];
        fputcsv($file, $headers);
        foreach ($data as $row) {
            $csvRow = [
                $row['id'] ?? '',
                $row['texto'] ?? '',
                $row['color'] ?? '',
                $row['checked'] ? 'true' : 'false'
            ];
            fputcsv($file, $csvRow);
        }
        fclose($file);
        $this->setCode(200);
        $this->setData(["message" => "Archivo guardado exitosamente"]);
    }
    function getEtiquetasCalendar($id)
    {
        $carpeta = $this->getCarpeta($id);
        $filePath ='../' .$carpeta. '/etiquetasCalendar' . $id . '.csv';
        if (file_exists($filePath)) {
            $this->setCode(200);
            $result = $this->getDataCSVbasic($filePath);
            $this->setData($result);
        } else {
            $this->setData(["message" => "El archivo no existe"]);
        }

    }
    function deleteEtiquetas($data, $id)
    {
        $carpeta = $this->getCarpeta($id);
        $filePath ='../' .$carpeta. '/etiquetasCalendar' . $id . '.csv';

        // Verificar si el archivo existe
        if (!file_exists($filePath)) {
            $this->setCode(404);
            $this->setData(["message" => "El archivo no existe"]);
            return;
        }

        // Leer el contenido del archivo
        $file = fopen($filePath, 'r');
        if ($file === false) {
            die('Error abriendo el archivo para lectura.');
        }

        $rows = [];
        $headers = fgetcsv($file); // Leer la primera fila (encabezados)

        while (($row = fgetcsv($file)) !== false) {
            // Revisar si la fila debe eliminarse
            $shouldDelete = false;
            foreach ($data as $deleteEtiqueta) {
                if ($row[0] === $deleteEtiqueta['id']) { // Comparando por 'Texto'
                    $shouldDelete = true;
                    break;
                }
            }

            if (!$shouldDelete) {
                $rows[] = $row; // Mantener las filas que no se eliminan
            }
        }
        fclose($file);

        // Reescribir el archivo CSV con las filas filtradas
        $file = fopen($filePath, 'w');
        if ($file === false) {
            die('Error abriendo el archivo para escritura.');
        }

        // Escribir los encabezados nuevamente
        fputcsv($file, $headers);

        // Escribir las filas restantes
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
        $this->setCode(200);
        $this->setData(["message" => "Etiquetas eliminadas exitosamente"]);
    }
    function saveEvento($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $userId = $data['id'];
        $requiredFields = ['id', 'title', 'start', 'color', 'descripcion', 'hora', 'etiquetaId'];
        foreach ($requiredFields as $field) {
            if (!isset($data['rows'][$field])) {
                $this->setCode(400);
                $this->setData(["error" => "Falta el campo: $field."]);
                return;
            }
        }

        $carpeta = $this->getCarpeta($userId);
        $dirPath = '../' . $carpeta;

        $filePath = $dirPath . '/eventos' . $userId . '.csv';
        $file = fopen($filePath, 'a');
        if ($file === false) {
            $this->setCode(500);
            $this->setData(["error" => "Error al abrir el archivo para escritura."]);
            return;
        }
        if (filesize($filePath) == 0) {
            $headers = ['ID', 'Title', 'Start', 'Color', 'Descripcion', 'Hora', 'EtiquetaID'];
            fputcsv($file, $headers);
        }

        $evento = [
            $data['rows']['id'], // Usar el ID proporcionado por el frontend
            $data['rows']['title'],
            $data['rows']['start'],
            $data['rows']['color'],
            strtolower($data['rows']['descripcion']),
            $data['rows']['hora'],
            $data['rows']['etiquetaId']
        ];
        if (fputcsv($file, $evento) === false) {
            $this->setCode(500);
            $this->setData(["error" => "Error al escribir el evento en el archivo CSV."]);
            fclose($file);
            return;
        }

        fclose($file);

        $fullFilePath = realpath($filePath);
        $this->setCode(200);
        $this->setData([
            "message" => "Evento guardado exitosamente en la ruta: $fullFilePath",
            "ruta" => $fullFilePath,
            "id" => $data['rows']['id']
        ]);
    }
    function getEventosCalendar($id) {
        $carpeta = $this->getCarpeta($id);
        $filePath = '../' . $carpeta . '/eventos' . $id . '.csv';

        if (file_exists($filePath)) {
            $this->setCode(200);
            $result = $this->getDataCSVbasic($filePath);

            // Convertir los datos del CSV a un array compatible con FullCalendar
            $events = [];
            foreach ($result as $row) {
                if (isset($row['ID'], $row['Title'], $row['Start'], $row['Color'], $row['EtiquetaID'])) {
                    $events[] = [
                        'id' => $row['ID'],
                        'title' => $row['Title'],
                        'start' => $row['Start'],
                        'color' => $row['Color'],
                        'description' => $row['Descripcion'],
                        'hora' => $row['Hora'],
                        'etiquetaId' => $row['EtiquetaID']
                    ];
                }
            }
            $this->setData($events);
        } else {
            $this->setCode(404);
            $this->setData(["message" => "El archivo no existe"]);
        }
    }





    function saveConfigWidjet($data) {
        $carpeta = $this->getCarpeta($data['id']);
        $filePath = '../' . $carpeta . '/widjetconfi' . $data['id'] . '.csv';
        $codigo = $this->generarCodigoUnico($data['id'], $carpeta . '/widjetconfi' . $data['id'] . '.csv');
        $file = fopen($filePath, 'w');
        fputcsv($file, ['color', 'border', 'radio', 'tooltip', 'message', 'title', 'image', 'codigo']);
        if (isset($data['rows']) && is_array($data['rows'])) {
            foreach ($data['rows'] as $row) {
                $csvRow = [
                    $row['color'] ?? '',
                    $row['border'] ?? '',
                    $row['radio'] ?? '',
                    $row['tooltip'] ?? '',
                    $row['message'] ?? '',
                    $row['title'] ?? '',
                    $row['image'] ?? '',
                    $codigo
                ];
                fputcsv($file, $csvRow);
            }
        }

        fclose($file);
        $this->setCode(200);
        $this->setData(["message" => ["text" => "Archivo guardado exitosamente", "codigo" => $codigo]]);
    }


    function getCarpeta($id)
    {
        $carpeta = 'generales';
        mysqli_next_result($this->mysqli);
        $dta = mysqli_query($this->mysqli, "SELECT tor.CARPETA FROM tbl_organizacion tor WHERE tor.ID_ORGANIZACION = '".$id."'") or die(mysqli_error($this->mysqli));

        if (mysqli_num_rows($dta) > 0) {
            $carpeta = $this->paramsReturn($dta)['CARPETA'];
        }
        return $carpeta;
    }
    function getDataCSVbasic($filePath){
        $result = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $result[] = $row;
            }
            fclose($handle);
        }
        return $result;
    }
    function getConfigWidjet($id,$setear = true) {
        $carpeta = $this->getCarpeta($id);
        $filePath = '../' . $carpeta . '/widjetconfi' . $id . '.csv';
        if (file_exists($filePath)) {
            $this->setCode(200);
           $result = $this->getDataCSVbasic($filePath);
           if ($setear)
            $this->setData($result);
           else
               return $result;
        } else {
            $this->setData(["message" => "El archivo no existe"]);
        }
    }
    function saveWidgetOrg($id){
        $carpeta = $this->getCarpeta($id);
        $sourceFile = '../widget/widjet1.js';
        $destinationFile = '../' . $carpeta . '/widjet' . $id . '.js';

        if (copy($sourceFile, $destinationFile)) {
            $this->setData(["message" => "Archivo creado exitosamente.","doc"=>$carpeta]);
            $this->setCode(200);
        } else {
            $this->setData(["message" => "Error al crear el archivo."]);
        }
    }

    function updateEvento($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $userId = $data['id'];
        $eventId = $data['eventId'];

        $requiredFields = ['title', 'start', 'color', 'descripcion', 'hora', 'etiquetaId'];
        foreach ($requiredFields as $field) {
            if (!isset($data['rows'][$field])) {
                $this->setCode(400);
                $this->setData(["error" => "Falta el campo: $field."]);
                return;
            }
        }

        $carpeta = $this->getCarpeta($userId);
        $filePath = '../' . $carpeta . '/eventos' . $userId . '.csv';

        if (!file_exists($filePath)) {
            $this->setCode(404);
            $this->setData(["error" => "El archivo no existe para el usuario."]);
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        if ($tempFile === false) {
            $this->setCode(500);
            $this->setData(["error" => "No se pudo crear un archivo temporal."]);
            return;
        }


        $updated = false;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            if (($temp = fopen($tempFile, "w")) !== FALSE) {
                $headers = fgetcsv($handle);
                fputcsv($temp, $headers);

                while (($row = fgetcsv($handle)) !== FALSE) {
                    if ($row[0] === $eventId) {
                        $row = [
                            $eventId,
                            $data['rows']['title'],
                            $data['rows']['start'],
                            $data['rows']['color'],
                            $data['rows']['descripcion'],
                            $data['rows']['hora'],
                            $data['rows']['etiquetaId']
                        ];
                        $updated = true;
                    }
                    fputcsv($temp, $row);
                }
                fclose($temp);
            }
            fclose($handle);
        }

        if ($updated) {
            if (!rename($tempFile, $filePath)) {
                $this->setCode(500);
                $this->setData(["error" => "No se pudo actualizar el archivo."]);
                return;
            }
            $this->setCode(200);
            $this->setData(["message" => "Evento actualizado exitosamente."]);
        } else {
            unlink($tempFile);
            $this->setCode(404);
            $this->setData(["error" => "No se encontró el evento para actualizar."]);
        }
    }

    function deleteEvento($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $userId = $data['id'];
        $eventId = $data['eventId'];

        $carpeta = $this->getCarpeta($userId);
        $filePath = '../' . $carpeta . '/eventos' . $userId . '.csv';

        if (!file_exists($filePath)) {
            $this->setCode(404);
            $this->setData(["error" => "El archivo no existe para el usuario."]);
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        if ($tempFile === false) {
            $this->setCode(500);
            $this->setData(["error" => "No se pudo crear un archivo temporal."]);
            return;
        }

        $deleted = false;
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            if (($temp = fopen($tempFile, "w")) !== FALSE) {
                $headers = fgetcsv($handle);
                fputcsv($temp, $headers);

                while (($row = fgetcsv($handle)) !== FALSE) {
                    if ($row[0] === $eventId) {
                        $deleted = true; // Marcamos que se ha limi
                        continue; // No escribimos esta fila
                    }
                    fputcsv($temp, $row);
                }
                fclose($temp);
            }
            fclose($handle);
        }

        if ($deleted) {
            if (!rename($tempFile, $filePath)) {
                $this->setCode(500);
                $this->setData(["error" => "No se pudo actualizar el archivo."]);
                return;
            }
            $this->setCode(200);
            $this->setData(["message" => "Evento eliminado exitosamente."]);
        } else {
            unlink($tempFile);
            $this->setCode(404);
            $this->setData(["error" => "No se encontró el evento para eliminar."]);
        }
    }



    function saveFileFlijo($data, $id)
    {
        if ($data) {
            $carpeta = $this->getCarpeta($id);
            $filePath = '../'.$carpeta.'/org' . $id . '.csv';

            $file = fopen($filePath, 'w');
            fputcsv($file, ['idPadre', 'item', 'referencia', 'subniveles', 'type']);

            $processRow = function($row, $parentId = null, $index = 0) use ($file, &$processRow) {
                $currentIndex = $parentId !== null ? $parentId . '.' . $index : $index;
                $subniveles = isset($row['subniveles']) ? implode('; ', array_map(function($sub, $subIndex) use ($currentIndex) {
                    return $currentIndex . '.' . $subIndex;
                }, $row['subniveles'], array_keys($row['subniveles']))) : '';

                $csvRow = [$currentIndex, $row['item'], $row['referencia'], $subniveles, $row['type'] ?? 'multiple'];
                fputcsv($file, $csvRow);

                foreach ($row['subniveles'] as $subIndex => $subnivel) {
                    $processRow($subnivel, $currentIndex, $subIndex);
                }
            };

            foreach ($data as $index => $row) {
                $processRow($row, null, $index);
            }
            fclose($file);
            $this->setCode(200);
            $this->setData(["message" => "Archivo guardado exitosamente"]);
        } else {
            $this->setData(["message" => "No se recibió ningún dato"]);
        }
    }

    function readFileFlijo($id,$setear = true)
    {
        $carpeta = $this->getCarpeta($id);
        $filePath = '../'.$carpeta.'/org' . $id . '.csv';
        if (file_exists($filePath)) {
            $fileHandle = fopen($filePath, 'r');
            $headers = fgetcsv($fileHandle);
            if ($headers === false) {
                $this->setData(["message" => "Error al leer los encabezados del archivo"]);
                fclose($fileHandle);
                return;
            }
            $items = [];
            while (($row = fgetcsv($fileHandle)) !== false) {
                $itemData = array_combine($headers, $row);
                $items[$itemData['idPadre']][] = $itemData;
            }
            fclose($fileHandle);
            $result = [];
            foreach ($items as $key => $itemGroup) {
                foreach ($itemGroup as $item) {
                    if (gettype($key) == 'integer')
                        $result[] = $this->buildItem($item, $items);
                }
            }
            $this->setCode(200);
            if ($setear)
             $this->setData($result);
            else
                return $result;
        } else {
            $this->setData(["message" => "El archivo no existe"]);
        }
    }


    function getDataChat($id)
    {
        $inf = $this->readFileFlijo($id,false);
        $config = $this->getConfigWidjet($id,false);
        if (isset($inf) && isset($config)){
            $fl = array('flujo'=>$inf);
            $cn = array('confi'=>$config);
            $this->setCode(200);
            $this->setData(array_merge($fl, $cn));
        }
    }


    function buildItem($item, $items)
    {
        if(!isset($item['type']))
            $item['type'] = "multiple";
        $itemData = [
            'item' => $item['item'],
            'referencia' => $item['referencia'],
            'subniveles' => [],
            'type' => $item['type'],
        ];
        // Si hay subniveles, procesarlos
        if (!empty($item['subniveles'])) {
            $subniveles = explode('; ', $item['subniveles']);
            foreach ($subniveles as $subnivel) {
                if (isset($items[$subnivel])) {
                    foreach ($items[$subnivel] as $subitem) {
                        $itemData['subniveles'][] = $this->buildItem($subitem, $items);
                    }
                }
            }
        }

        return $itemData;
    }

    function generarCodigoUnico($id,$archive) {
        $prefijo = 'WDJ';
        $codigo = '';
        $consulta = $this->mysqli->prepare("SELECT CODIGO FROM tbl_codigosLeads WHERE FK_ID_ORGANIZACION = ?");
        $consulta->bind_param('i', $id);
        $consulta->execute();
        $consulta->bind_result($codigoExistente);
        $consulta->fetch();
        $consulta->close();
        if ($codigoExistente)
            return $codigoExistente;
        do {
            $letras = '';

            for ($i = 0; $i < 5; $i++) {
                $letras .= chr(random_int(65, 90));
            }

            $codigo = $prefijo . $letras;
            $consulta = $this->mysqli->prepare("SELECT COUNT(*) FROM tbl_codigosLeads WHERE CODIGO = ?");
            $consulta->bind_param('s', $codigo);
            $consulta->execute();
            $consulta->bind_result($existe);
            $consulta->fetch();
            $consulta->close();

        } while ($existe > 0);

        $insertarConsulta = $this->mysqli->prepare("INSERT INTO tbl_codigosLeads (FK_ID_ORGANIZACION	,CODIGO,ARCHIVO, STATUS) VALUES (?, ?, ?, ?)");
        $status = 1;

        $insertarConsulta->bind_param('issi', $id, $codigo,$archive, $status);
        $insertarConsulta->execute();
        $insertarConsulta->close();

        return $codigo;
    }
    function saveArchive($data)
    {
        $filename = $data['filename'];
        if (!isset($data['content']) || empty($data['content'])) {
            $this->setData([ "Error: el valor 'content' no está definido o es inválido." ]);
            return;
        }
        $cont = $data['content'];
        $filedata = $data['filedata'];
        if (!is_dir($cont)) {
            if (!mkdir($cont, 0777, true)) {
                $this->setData([ "Error: no se pudo crear el directorio: " . $cont ]);
                return;
            }
        }
        $file_to_delete = $cont . basename($filename);
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }
        if (preg_match('/^data:(\w+\/\w+);base64,/', $filedata, $match)) {
            $filedata = substr($filedata, strpos($filedata, ',') + 1);
            $filedata = base64_decode($filedata);
            $filepath = $cont . $filename;
            if (file_put_contents($filepath, $filedata)) {
                $this->setCode(200);
                $this->setData([ "Archivo guardado exitosamente: " . $filename ]);
            } else {
                $this->setData([ "Error al guardar el archivo: " . $filename ]);
            }
        } else {
            $this->setData(['Formato de datos no válido.']);
        }
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
}