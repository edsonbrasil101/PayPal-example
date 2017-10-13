<?php

function logData($data)
{
    $logFile = fopen("logFile.txt", "a");
    $datetime = date("Y-m-d H:i:s");
    
    fwrite($logFile, $datetime . ": " . $data . "\r\n");
    fclose($logFile);
    
    echo $datetime . ": " . $data . "<br><br>";
}

function arrayToText($array)
{
    $buffer = "";
    
    foreach(array_keys($array) as $arrayKey)
    {
        $buffer .= "[".$arrayKey."] = ".$array[$arrayKey].", ";
    }
    
    return $buffer;
}

function orderItensToText($arrayOrder)
{
    // descrição;valor;quantidade&descrição;valor;quantidade&...
    
    $description = "";
    $itemCounter = 0;
    
    while(true)
    {
        if(isset($arrayOrder["description_item_".$itemCounter]))
        {
            $item = $arrayOrder["description_item_".$itemCounter];
            $item .= ";";
            $item .= $arrayOrder["value_item_".$itemCounter];
            $item .= ";";
            $item .= $arrayOrder["quantity_item_".$itemCounter];
            
            if($description != "")
            {
                $description .= "&";
            }
            
            $description .= $item;
            
            $itemCounter++;
        }
        else
        {
            break;
        }
    }
    
    return $description;
}

function textToOrderItens($description)
{
    $arrayItens = array();
    $itemCounter = 0;
    
    $itens = explode("&", $description);
    
    foreach($itens as $itemFull)
    {
        $item = explode(";", $itemFull);
        
        $arrayItens["description_item_" . $itemCounter] = $item[0];
        $arrayItens["value_item_" . $itemCounter] = $item[1];
        $arrayItens["quantity_item_" . $itemCounter] = $item[2];
        
        $itemCounter++;
    }
    
    return $arrayItens;
}

?>