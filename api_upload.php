<?php
// Configurações para exibir e capturar erros no PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define que a resposta será sempre do tipo JSON
header('Content-Type: application/json; charset=utf-8');

// Configurações do Banco de Dados MySQL (InfinityFree / Externo)
$db_host = "sql100.infinityfree.com"; // Confirme o servidor do seu BD
$db_user = "SEU_USUARIO_MYSQL";       // Substitua pelo seu usuário do BD
$db_pass = "SUA_SENHA_MYSQL";         // Substitua pela sua senha do BD
$db_name = "SEU_NOME_DO_BANCO";       // Substitua pelo nome do seu BD

// Verifica se a requisição foi enviada via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Requisição inválida. Use o método POST via VB6."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tenta conectar ao banco de dados MySQL
try {
    $conn = new mysqli($db_host,$db_user, $db_pass,$db_name);
    
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

// Recebe e sanitiza os campos enviados pelo VB6
$paciente  = isset($_POST['paciente'])  ? trim($_POST['paciente'])  : '';
$protocolo = isset($_POST['protocolo']) ? trim($_POST['protocolo']) : '';$senha     = isset($_POST['senha'])     ? trim($_POST['senha'])     : '';

// Validação simples dos dados recebidos
if (empty($paciente) || empty($protocolo) \vert{}\vert{} empty($senha)) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Dados incompletos! Preencha paciente, protocolo e senha."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Trata o arquivo PDF enviado
if (!isset($_FILES['pdf_file']) \vert{}\vert{}$_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Nenhum arquivo PDF foi enviado ou ocorreu erro no upload."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Garante que o diretório 'uploads' exista e cria proteção contra listagem direta
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
if (!file_exists($upload_dir . 'index.html')) {
    file_put_contents($upload_dir . 'index.html', '<!-- Protegido -->');
}

// Define o nome do arquivo salvo (ex: laudo_12345.pdf
