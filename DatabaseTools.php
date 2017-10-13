<?php

    /*
    CREATE TABLE orders
    (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(64) NOT NULL,
        email VARCHAR(64) NOT NULL,
        description TINYTEXT NOT NULL,
        total DOUBLE NOT NULL,
        tokenpaypal VARCHAR(64) NOT NULL DEFAULT '',
        transactionidpaypal VARCHAR(64) NOT NULL DEFAULT '',
        situation VARCHAR(64) NOT NULL,
        PRIMARY KEY (id)
    );
    */
    
    // Dados de conexão com o banco de dados
    define('MYSQL_HOST',        'exemplo.mysql.seusite.com.br'); // Endereço do seu servidor de banco de dados
    define('MYSQL_DATABASE',    'meuBancoDeExemplo'); // Nome do seu banco de dados
    define('MYSQL_USER',        'meuUsuario'); // Usuário do seu banco de dados
    define('MYSQL_PASSWORD',    '**********'); // Senha do banco de dados
    
    require_once("AuxiliarTools.php");
    
    function getConnection()
    {
        try
        {
            $connection = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
            $connection->exec("set names utf8");
            return $connection;
        }
        catch(PDOException $e)
        {
            logData("Falha na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    function saveOrder($arrayOrder)
    {
        $connection = getConnection();
        
        $sql = "INSERT INTO orders
                (name, email, description, total, situation)
                VALUES (?, ?, ?, ?, ?)";
                
        $stmt = $connection->prepare($sql);
        
        $stmt->bindParam(1, $arrayOrder["name"]);
        $stmt->bindParam(2, $arrayOrder["email"]);
        
        $description = orderItensToText($arrayOrder);
        $stmt->bindParam(3, $description);
        
        $stmt->bindParam(4, $arrayOrder["total"]);
        $stmt->bindParam(5, $arrayOrder["situation"]);
        
        $stmt->execute();
        
        if($stmt->errorCode() != "00000")
        {
            $erro = "Erro código " . $stmt->errorCode() . ": ";
            $erro .= implode(", ", $stmt->errorInfo());
            
            logData("Falha na inserção de pedido: " . $erro);
            return null;
        }
        
        $rs = $connection->prepare("SELECT id FROM orders
                                    WHERE email = ?
                                    ORDER BY id DESC LIMIT 1");
        
        $rs->bindParam(1, $arrayOrder["email"]);
        
        if($rs->execute())
        {
            if($registro = $rs->fetch(PDO::FETCH_OBJ))
            {
                $arrayOrder["id"] = $registro->id;
                return $arrayOrder;
            }
            else
            {
                logData("Pedido não localizado para o usuário ".$arrayOrder["email"]);
            }
        }
        else
        {
            logData("Falha na captura de id do pedido para o usuário ".$arrayOrder["email"]);
        }
    }
    
    function updateOrder($arrayOrder)
    {
        $connection = getConnection();
        
        $sql = "UPDATE orders SET
                tokenpaypal = ?,
                situation = ?,
                transactionidpaypal = ?
                WHERE id = ?";
                
        $stmt = $connection->prepare($sql);
        
        $stmt->bindParam(1, $arrayOrder["tokenpaypal"]);
        $stmt->bindParam(2, $arrayOrder["situation"]);
        $stmt->bindParam(3, $arrayOrder["transactionidpaypal"]);
        $stmt->bindParam(4, $arrayOrder["id"]);
        
        $stmt->execute();
        
        if($stmt->errorCode() != "00000")
        {
            $erro = "Falha na atualização: ".$stmt->errorCode() . ": ";
            $erro .= implode(", ", $stmt->errorInfo());
            logData($erro);
        }
    }
    
    function getOrder($tokenPayPal)
    {
        $connection = getConnection();
        
        $rs = $connection->prepare("SELECT * FROM orders WHERE tokenpaypal = ?");
        $rs->bindParam(1, $tokenPayPal);
        
        if($rs->execute())
        {
            if($registro = $rs->fetch(PDO::FETCH_OBJ))
            {
                logData(1);
                
                $arrayOrder = array();
                $arrayOrder["id"] = $registro->id;
                $arrayOrder["name"] = $registro->name;
                $arrayOrder["email"] = $registro->email;
                $arrayOrder["total"] = $registro->total;
                $arrayOrder["tokenpaypal"] = $registro->tokenpaypal;
                $arrayOrder["transactionidpaypal"] = $registro->transactionidpaypal;
                $arrayOrder["situation"] = $registro->situation;
                
                logData(2);
                
                $arrayItens = textToOrderItens($registro->description);
                $arrayOrder = array_merge($arrayOrder, $arrayItens);
                
                logData(3);
                
                return $arrayOrder;
                
                //$registro->id
            }
            else
            {
                logData("Pedido não localizado para o token: " . $tokenPayPal);
                return null;
            }
        }
        else
        {
            logData("Falha na captura de pedido do token: " . $tokenPayPal);
            return null;
        }
    }
    

?>