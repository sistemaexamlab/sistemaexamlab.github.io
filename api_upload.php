<?php
// api_upload.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$host = "sql100.infinityfree.com";
$user = "if0_41199215";
$pass = "Q1x3SVeaWuHJsI";
$db   = "if0_41199215_portal_exames";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de conexão ao banco"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $paciente_nome = $_POST['paciente_nome'] ?? '';
    $protocolo     = $_POST['protocolo'] ?? '';
    $senha_cliente = $_POST['senha_cliente'] ?? '';
    
    if (!isset($_FILES['arquivo'])) {
        echo json_encode(["sucesso" => false, "mensagem" => "Arquivo não enviado"]);
        exit;
    }

    $pdf_nome = basename($_FILES['arquivo']['name']);
    
    // Garanta que a pasta 'uploads/' existe no servidor
    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }
    
    $destino = "uploads/" . $pdf_nome;

    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
        $stmt = $conn->prepare("INSERT INTO resultados (paciente_nome, protocolo, senha, arquivo_pdf, data_cadastro) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $paciente_nome, $protocolo, $senha_cliente, $pdf_nome);
        
        if ($stmt->execute()) {
            echo json_encode(["sucesso" => true, "mensagem" => "Laudo cadastrado com sucesso"]);
        } else {
            echo json_encode(["sucesso" => false, "mensagem" => "Erro no banco: " . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao mover arquivo para a pasta uploads"]);
    }
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Requisição inválida"]);
}