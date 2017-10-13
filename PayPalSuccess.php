<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Integração PayPal (Softblue)</title>
    </head>
    <body>

    <?php
    
    require("AuxiliarTools.php");
    logData("PayPalSuccess.php: Possível transação recebida.");
    
    $requestText = arrayToText($_REQUEST);
    logData("PayPalSuccess.php: Dados recebidos via REQUEST: " . $requestText);
    
    $payPalToken = $_REQUEST["token"];
    logData("Token enviado para o getOrder: " . $payPalToken);
    
    require("DatabaseTools.php");
    $arrayOrder = getOrder($payPalToken);
    logData("GetOrder: " . arrayToText($arrayOrder));
    
    require("PayPalTools.php");
    $requestNvp = createNvpArray($arrayOrder, PAYPAL_GET_EXPRESS_CHECKOUT, $_REQUEST);
    logData(PAYPAL_GET_EXPRESS_CHECKOUT . " (request): " . arrayToText($requestNvp));
    
    $responseNvp = sendNvpRequest($requestNvp);
    logData(PAYPAL_GET_EXPRESS_CHECKOUT . " (response): " . arrayToText($responseNvp));
    
    $isValid = true;
    
    if($arrayOrder["situation"] != "Aguardando pagamento")
    {
        $isValid = false;
        logData("Pedido já teve o pagamento aprovado anteriormente. Código do pedido: " . $arrayOrder["id"]);
    }
    else if($arrayOrder["total"] != $responseNvp["PAYMENTREQUEST_0_AMT"])
    {
        $isValid = false;
        logData("Valor confirmado no PayPal diferente do esperado. Código do pedido: " . $arrayOrder["id"]);
    }
    
    if($isValid == true)
    {
        // Sequência...
        
        $requestNvp = createNvpArray($arrayOrder, PAYPAL_DO_EXPRESS_CHECKOUT, $_REQUEST);
        logData(PAYPAL_DO_EXPRESS_CHECKOUT . " (request): " . arrayToText($requestNvp));
        
        $responseNvp = sendNvpRequest($requestNvp);
        logData(PAYPAL_DO_EXPRESS_CHECKOUT . " (response): " . arrayToText($responseNvp));
        
        if($responseNvp["PAYMENTINFO_0_ACK"] == "Success")
        {
            $arrayOrder["situation"] = "Pagamento aprovado";
            $arrayOrder["transactionidpaypal"] = $responseNvp["PAYMENTINFO_0_TRANSACTIONID"];
            updateOrder($arrayOrder);
            
            $message = "Pagamento recebido.";
        }
        else
        {
            logData("Falha no DoExpressCheckout: " . arrayToText($responseNvp));
        }
    }
    else
    {
        $message = "Pagamento não autorizado.";
    }
    
    echo $message;
    
    ?>

    </body>
</html>