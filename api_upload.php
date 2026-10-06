<?php
// Exibe erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Configurações do Banco de Dados
$db_host = "sql100.infinityfree.com";
$db_user = "SEU_USUARIO_MYSQL";
$db_pass = "SUA_SENHA_MYSQL";
$db_name = "SEU_NOME_DO_BANCO";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Requisição inválida. Use o método POST via VB6."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o Banco de Dados: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Erro no banco: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$paciente  = isset($_POST['paciente'])  ? trim($_POST['paciente'])  : '';
$protocolo = isset($_POST['protocolo']) ? trim($_POST['protocolo']) : '';
$senha     = isset($_POST['senha'])     ? trim($_POST['senha'])     : '';

if (empty($paciente) || empty($protocolo) || empty($senha)) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Dados incompletos! Preencha paciente, protocolo e senha."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Nenhum arquivo PDF foi enviado ou ocorreu erro no upload."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
if (!file_exists($upload_dir . 'index.html')) {
    file_put_contents($upload_dir . 'index.html', '<!-- Protegido -->');
}

$nome_arquivo_pdf = "laudo_" . preg_replace('/[^A-Za-z0-9_\-]/', '', $protocolo) . ".pdf";
$caminho_final    = $upload_dir . $nome_arquivo_pdf;

if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $caminho_final)) {
    
    $stmt = $conn->prepare("INSERT INTO resultados (paciente_nome, protocolo, senha, arquivo_pdf, data_envio) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE paciente_nome = VALUES(paciente_nome), senha = VALUES(senha), arquivo_pdf = VALUES(arquivo_pdf)");
    
    if ($stmt) {
        $stmt->bind_param("ssss", $paciente, $protocolo, $senha, $nome_arquivo_pdf);
        
        if ($stmt->execute()) {
            echo json_encode([
                "sucesso"  => true,
                "mensagem" => "Laudo cadastrado com sucesso!",
                "arquivo"  => $nome_arquivo_pdf
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "sucesso"  => false,
                "mensagem" => "Erro ao salvar no banco: " . $stmt->error
            ], JSON_UNESCAPED_UNICODE);
        }
        $stmt->close();
    } else {
        echo json_encode([
            "sucesso"  => false,
            "mensagem" => "Erro ao preparar SQL: " . $conn->error
        ], JSON_UNESCAPED_UNICODE);
    }

} else {
    echo json_encode([
        "sucesso"  => false,
        "mensagem" => "Falha ao mover e gravar o arquivo PDF no servidor."
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
