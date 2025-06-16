<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $folder = $_POST['folder'];
    $funcionario_id = $_POST['funcionario_id'];
    $empresa_id = $_SESSION['id_empresa'];

    // Verifica se o funcionário pertence à empresa
    $checkEmployee = mysqli_prepare($conn, "SELECT id_fun FROM funcionario WHERE id_fun = ? AND empresa_id = ?");
    mysqli_stmt_bind_param($checkEmployee, 'ii', $funcionario_id, $empresa_id);
    mysqli_stmt_execute($checkEmployee);
    mysqli_stmt_store_result($checkEmployee);

    if (mysqli_stmt_num_rows($checkEmployee) > 0) {
        $file = $_FILES['document'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        // Extrai a extensão do arquivo
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Extensões permitidas
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { // Limite de 5MB
                    // Gera um nome único para o arquivo
                    $fileNameNew = uniqid('', true) . '.' . $fileExt;
                    $fileDestination = 'uploads/' . $fileNameNew;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        // Insere no banco de dados
                        $sql = "INSERT INTO documentos (titulo, tipo, data, descricao, anexo, num_funcionario, folder) 
                                VALUES (?, ?, NOW(), ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        $descricao = "Documento enviado"; // Você pode personalizar isso
                        mysqli_stmt_bind_param($stmt, 'ssssis', $fileName, $fileExt, $descricao, $fileNameNew, $funcionario_id, $folder);
                        mysqli_stmt_execute($stmt);

                        echo json_encode(['success' => true, 'message' => 'Upload realizado com sucesso!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao mover o arquivo.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'O arquivo é muito grande.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado ou não pertence à sua empresa.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
}
?>