<?php
    ob_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Integração PayPal (Softblue)</title>
    </head>
    <body>

<?php
    
    $arrayOrder = array("name" => $_POST["formName"],
                        "email" => $_POST["formEmail"],
                        "description_item_0" => "Matrix",
                        "value_item_0" => "1.13",
                        "quantity_item_0" => "1",
                        "description_item_1" => "Sentinela",
                        "value_item_1" => "0.02",
                        "quantity_item_1" => "1",
                        "total" => "1.15",
                        "situation" => "Aguardando pagamento");
    
    print_r($arrayOrder);
    
    require("DatabaseTools.php");
    $arrayOrder = saveOrder($arrayOrder);

    // Cadastra o pedido no site do PayPal
    require("PayPalTools.php");
    $requestNvp = createNvpArray($arrayOrder, PAYPAL_SET_EXPRESS_CHECKOUT, null);
    
    // Logs
    require("AuxiliarTools.php");
    logData(PAYPAL_SET_EXPRESS_CHECKOUT . " (request): " . arrayToText($requestNvp));
    
    // Envia o SetExpressCheckout
    $responseNvp = sendNvpRequest($requestNvp);
    logData(PAYPAL_SET_EXPRESS_CHECKOUT . " (response): " . arrayToText($responseNvp));
    
    // Atualiza o banco com o token
    $arrayOrder["tokenpaypal"] = $responseNvp["TOKEN"];
    updateOrder($arrayOrder);
    
    $query = array("cmd" => "_express-checkout",
                   "useraction" => "commit",
                   "token" => $arrayOrder["tokenpaypal"]);
    
    require("PayPalConfigurations.php");
    $redirectURL = sprintf("%s?%s", $payPalUrlConnection, http_build_query($query));
    
    header("Location: " . $redirectURL);

    
    
    
    

?>

    </body>
</html>
<?php
    ob_flush();
?>