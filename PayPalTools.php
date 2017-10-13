<?php

    // Documentação:
    // https://developer.paypal.com/webapps/developer/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/

    define("PAYPAL_SET_EXPRESS_CHECKOUT", "SetExpressCheckout (Step 1)");
    define("PAYPAL_GET_EXPRESS_CHECKOUT", "GetExpressCheckout (Step 2)");
    define("PAYPAL_DO_EXPRESS_CHECKOUT", "DoExpressCheckout (Step 3)");

    function createNvpArray($arrayOrder, $type, $extra)
    {
        require("PayPalConfigurations.php");
        
        $requestNvp = array();
        $requestNvp["USER"] = $payPalUser;
        $requestNvp["PWD"] = $payPalPassword;
        $requestNvp["SIGNATURE"] = $payPalSignature;
        $requestNvp["VERSION"] = "108.0";
        
        switch($type)
        {
            case PAYPAL_SET_EXPRESS_CHECKOUT:
                $requestNvp["METHOD"] = "SetExpressCheckout";
                break;
            
            case PAYPAL_GET_EXPRESS_CHECKOUT:
                $requestNvp["METHOD"] = "GetExpressCheckoutDetails";
                break;
            
            case PAYPAL_DO_EXPRESS_CHECKOUT:
                $requestNvp["METHOD"] = "DoExpressCheckoutPayment";
                break;
        }
        
        if($type == PAYPAL_GET_EXPRESS_CHECKOUT)
        {
            $requestNvp["SUBJECT"] = $payPalSubject;
        }
        
        if($type == PAYPAL_GET_EXPRESS_CHECKOUT || $type == PAYPAL_DO_EXPRESS_CHECKOUT)
        {
            $requestNvp["TOKEN"] = $extra["token"];
            // TODO:
        }
        
        if($type == PAYPAL_DO_EXPRESS_CHECKOUT)
        {
            $requestNvp["PAYERID"] = $extra["PayerID"];
        }
        
        if($type == PAYPAL_DO_EXPRESS_CHECKOUT || $type == PAYPAL_SET_EXPRESS_CHECKOUT)
        {
            $requestNvp["PAYMENTREQUEST_0_PAYMENTACTION"] = "SALE";
            $requestNvp["PAYMENTREQUEST_0_CURRENCYCODE"] = "BRL";
        }
        
        if($type == PAYPAL_SET_EXPRESS_CHECKOUT)
        {
            $requestNvp["LOCALECODE"] = "BR";
            $requestNvp["LC"] = "BR";
            $requestNvp["EMAIL"] = $arrayOrder["email"];
            $requestNvp["PAYMENTREQUEST_0_CUSTOM"] = $arrayOrder["id"];
            $requestNvp["PAYMENTREQUEST_0_INVNUM"] = $arrayOrder["id"];
            $requestNvp["PAYMENTREQUEST_0_SHIPTONAME"] = utf8_decode($arrayOrder["name"]);
            
            $requestNvp["RETURNURL"] = "http://code.softblue.com.br/phppaypal/PayPalSuccess.php";
            $requestNvp["CANCELURL"] = "http://code.softblue.com.br/phppaypal/PayPalCancel.php";
            $requestNvp["BUTTONSOURCE"] = "BR_EC_EMPRESA";
        }
        
        $itemCounter = 0;
        
        while(true)
        {
            if(isset($arrayOrder["description_item_" . $itemCounter]))
            {
                $requestNvp["L_PAYMENTREQUEST_0_NAME".$itemCounter] = utf8_decode($arrayOrder["description_item_".$itemCounter]);
                $requestNvp["L_PAYMENTREQUEST_0_AMT".$itemCounter] = utf8_decode($arrayOrder["value_item_".$itemCounter]);
                $requestNvp["L_PAYMENTREQUEST_0_QTY".$itemCounter] = utf8_decode($arrayOrder["quantity_item_".$itemCounter]);
                $requestNvp["L_PAYMENTREQUEST_0_ITEMCATEGORY".$itemCounter] = "Digital";
                
                $itemCounter++;
            }
            else
            {
                break;
            }
        }
        
        $requestNvp["PAYMENTREQUEST_0_AMT"] = $arrayOrder["total"];
        return $requestNvp;
    }
    
    
    function sendNvpRequest(array $requestNvp)
    {
        require("PayPalConfigurations.php");
               
        //Executando a operação
        $curl = curl_init();

        // Documentação dos parâmetros:
        // http://php.net/manual/pt_BR/function.curl-setopt.php
        curl_setopt($curl, CURLOPT_URL, $payPalNvpUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestNvp));

        $response = urldecode(curl_exec($curl));

        curl_close($curl);
      
        //Tratando a resposta
        $responseNvp = array();
      
        if(preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches))
        {
            foreach($matches['name'] as $offset => $name)
            {
                $responseNvp[$name] = $matches['value'][$offset];
            }
        }

        //Verificando se deu tudo certo e, caso algum erro tenha ocorrido, gravamos um log para depuração.
        if (isset($responseNvp['ACK']) && $responseNvp['ACK'] != 'Success')
        {
            for ($i = 0; isset($responseNvp['L_ERRORCODE' . $i]); ++$i)
            {
                $message = sprintf("PayPal NVP %s[%d]: %s\n",
                                   $responseNvp['L_SEVERITYCODE' . $i],
                                   $responseNvp['L_ERRORCODE' . $i],
                                   $responseNvp['L_LONGMESSAGE' . $i]);
      
                logData($message);
                
                // PayPal NVP Error[10004]: You are not signed up to accept payment for digitally delivered goods.
                // Tem que entrar em contato com o PayPal e solicitar a habilitação
            }
        }

        return $responseNvp;
    }
    
?>