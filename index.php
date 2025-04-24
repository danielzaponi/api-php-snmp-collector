<?php
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    // Adiciona cabeçalhos para permitir CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json; charset=UTF-8");

    // Add "library" folder to include path
    set_include_path(get_include_path() . PATH_SEPARATOR . 'library');

    require_once './library/Kohut/SNMP/Printer.php';

    // IP address of printer in network
    $ip = filter_var($_GET['ip'],FILTER_VALIDATE_IP);
    if(!$ip){
        $data = [
            'status' => false,
            'message' => "Endereco de IP informado é inválido!"
        ];
    }
    exec("ping -n 1 -w 1 $ip",$output, $status);
    if($status === 0){
        try {
            $printer = new Kohut_SNMP_Printer($ip);
            $startTime = microtime(true);
            $data = $printer->getAllInfo();
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $formattedTime = number_format($executionTime, 3,'.','');
            $data['execution_time'] = $formattedTime . " seconds";
            $data['status'] = true;
        } catch (Kohut_SNMP_Exception $e) {
            $data = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }else{
        $data = [
            'status' => false,
            'message' => "Impressora IP $ip indisponivel no momento. Tente novamente mais tarde."
        ];
    }
    echo json_encode($data,JSON_PRETTY_PRINT);
?>