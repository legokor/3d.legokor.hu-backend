<?php

if(!isset($_REQUEST)) return;

$config = parse_ini_file("config.ini");
foreach($config as $key => $value){
    define($key,$value);
}

echo handleRequest($_REQUEST,$_SERVER['REQUEST_METHOD'],$_FILES);
exit();

function createResponse($status, $msg ="" ){ 
    $response = ["status" => $status];
    if($status == "error") http_response_code(400);
    else http_response_code(200);

    if(!empty($msg)) $response['msg'] = $msg;
    return json_encode($response);
}

function createPUTRequest($path,$data){
    $ch = curl_init(TRELLO_BASE_URL.$path);
  
    curl_setopt($ch, CURLOPT_URL,TRELLO_BASE_URL.$path);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
}

function createCard($data){
    $desc = "";
    foreach($data as $name => $value){
        $desc .= $name.": ".$value."\n";
    }

    $baseParams = [
        "key" => TRELLO_API_KEY,
        "token" => TRELLO_API_TOKEN,
        "name" => $data['name']." - ".$data['email'],
        "idList" => TRELLO_BOARD,
        "desc" => $desc
    ];
    return createPUTRequest("/cards",$baseParams);
}

function uploadFile($orderID, $fileName){
    if(!is_dir("upload")) mkdir("upload/");
    $targetDir = "upload/$orderID/";
    var_dump($targetDir);
    mkdir("./".$targetDir);
    $targetFile = $targetDir.basename($_FILES[$fileName]["name"]);
    move_uploaded_file($_FILES['model']["tmp_name"], $targetFile);
    return $targetFile;
}

function handleRequest($request,$requestType,$files = []){
    if($requestType != "POST") return createResponse("error","wrong request");

    if(!isset($request) || empty($request) ) return createResponse("error","missing data");
   
    $requiredFields = ["name","email"];
    
    foreach($requiredFields as $field){
        if(empty($request[$field])) return createResponse("error","missing fields");
    }

    $request["orderid"] = md5(time());
    
    if(!empty($files)){
        $fileName = uploadFile($request['orderid'],"model");
        $request['model'] = SERVER_HOST.$fileName;
    }

    $response = createCard($request);
    if(isset($response['id'])) return createResponse("ok");
    else return createResponse("error","failed to create");
}

?>